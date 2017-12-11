<?php

class AbuseFilterParser {
	public $mCode, $mTokens, $mPos, $mCur, $mShortCircuit, $mAllowShort, $mLen;

	/**
	 * @var AbuseFilterVariableHolder
	 */
	public $mVars;

	// length,lcase,ucase,ccnorm,rmdoubles,specialratio,rmspecials,norm,count
	public static $mFunctions = array(
		'lcase' => 'funcLc',
		'ucase' => 'funcUc',
		'length' => 'funcLen',
		'string' => 'castString',
		'int' => 'castInt',
		'float' => 'castFloat',
		'bool' => 'castBool',
		'norm' => 'funcNorm',
		'ccnorm' => 'funcCCNorm',
		'specialratio' => 'funcSpecialRatio',
		'rmspecials' => 'funcRMSpecials',
		'rmdoubles' => 'funcRMDoubles',
		'rmwhitespace' => 'funcRMWhitespace',
		'count' => 'funcCount',
		'rcount' => 'funcRCount',
		'ip_in_range' => 'funcIPInRange',
		'contains_any' => 'funcContainsAny',
		'substr' => 'funcSubstr',
		'strlen' => 'funcLen',
		'strpos' => 'funcStrPos',
		'str_replace' => 'funcStrReplace',
		'rescape' => 'funcStrRegexEscape',
		'set' => 'funcSetVar',
		'set_var' => 'funcSetVar',
	);

	// Functions that affect parser state, and shouldn't be cached.
	public static $ActiveFunctions = array(
		'funcSetVar',
	);

	public static $mKeywords = array(
		'in' => 'keywordIn',
		'like' => 'keywordLike',
		'matches' => 'keywordLike',
		'contains' => 'keywordContains',
		'rlike' => 'keywordRegex',
		'irlike' => 'keywordRegexInsensitive',
		'regex' => 'keywordRegex'
	);

	public static $funcCache = array();

	/**
	 * Create a new instance
	 *
	 * @param $vars AbuseFilterVariableHolder
	 */
	public function __construct( $vars = null ) {
		$this->resetState();
		if ( $vars instanceof AbuseFilterVariableHolder ) {
			$this->mVars = $vars;
		}
	}

	public function resetState() {
		$this->mCode = '';
		$this->mTokens = array();
		$this->mVars = new AbuseFilterVariableHolder;
		$this->mPos = 0;
		$this->mShortCircuit = false;
		$this->mAllowShort = true;
	}

	/**
	 * @param $filter
	 * @return array|bool
	 */
	public function checkSyntax( $filter ) {
		try {
			$origAS = $this->mAllowShort;
			$this->mAllowShort = false;
			$this->parse( $filter );
		} catch ( AFPUserVisibleException $excep ) {
			$this->mAllowShort = $origAS;

			return array( $excep->getMessageObj()->text(), $excep->mPosition );
		}
		$this->mAllowShort = $origAS;

		return true;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setVar( $name, $value ) {
		$this->mVars->setVar( $name, $value );
	}

	/**
	 * @param $vars
	 */
	public function setVars( $vars ) {
		if ( is_array( $vars ) ) {
			foreach ( $vars as $name => $var ) {
				$this->setVar( $name, $var );
			}
		} elseif ( $vars instanceof AbuseFilterVariableHolder ) {
			$this->mVars->addHolders( $vars );
		}
	}

	/**
	 * @return AFPToken
	 */
	protected function move() {
		list( $this->mCur, $this->mPos ) = $this->mTokens[$this->mPos];
	}

	/**
	 * getState() function allows parser state to be rollbacked to several tokens back
	 * @return AFPParserState
	 */
	protected function getState() {
		return new AFPParserState( $this->mCur, $this->mPos );
	}

	/**
	 * setState() function allows parser state to be rollbacked to several tokens back
	 * @param AFPParserState $state
	 */
	protected function setState( AFPParserState $state ) {
		$this->mCur = $state->token;
		$this->mPos = $state->pos;
	}

	/**
	 * @return mixed
	 * @throws AFPUserVisibleException
	 */
	protected function skipOverBraces() {
		if ( !( $this->mCur->type == AFPToken::TBRACE && $this->mCur->value == '(' ) ||
			!$this->mShortCircuit
		) {
			return;
		}

		$braces = 1;
		while ( $this->mCur->type != AFPToken::TNONE && $braces > 0 ) {
			$this->move();
			if ( $this->mCur->type == AFPToken::TBRACE ) {
				if ( $this->mCur->value == '(' ) {
					$braces++;
				} elseif ( $this->mCur->value == ')' ) {
					$braces--;
				}
			}
		}
		if ( !( $this->mCur->type == AFPToken::TBRACE && $this->mCur->value == ')' ) ) {
			throw new AFPUserVisibleException( 'expectednotfound', $this->mCur->pos, array( ')' ) );
		}
	}

	/**
	 * @param $code
	 * @return bool
	 */
	public function parse( $code ) {
		return $this->intEval( $code )->toBool();
	}

	/**
	 * @param $filter
	 * @return string
	 */
	public function evaluateExpression( $filter ) {
		return $this->intEval( $filter )->toString();
	}

	/**
	 * @param $code
	 * @return AFPData
	 */
	function intEval( $code ) {
		// Setup, resetting
		$this->mCode = $code;
		$this->mTokens = AbuseFilterTokenizer::tokenize( $code );
		$this->mPos = 0;
		$this->mLen = strlen( $code );
		$this->mShortCircuit = false;

		$result = new AFPData();
		$this->doLevelEntry( $result );

		return $result;
	}

	/**
	 * @param $a
	 * @param $b
	 * @return int
	 */
	static function lengthCompare( $a, $b ) {
		if ( strlen( $a ) == strlen( $b ) ) {
			return 0;
		}

		return ( strlen( $a ) < strlen( $b ) ) ? -1 : 1;
	}

	/* Levels */

	/**
	 * Handles unexpected characters after the expression
	 *
	 * @param $result AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function doLevelEntry( &$result ) {
		$this->doLevelSemicolon( $result );

		if ( $this->mCur->type != AFPToken::TNONE ) {
			throw new AFPUserVisibleException(
				'unexpectedatend',
				$this->mCur->pos, array( $this->mCur->type )
			);
		}
	}

	/**
	 * Handles multiple expressions
	 * @param $result AFPData
	 */
	protected function doLevelSemicolon( &$result ) {
		do {
			$this->move();
			if ( $this->mCur->type != AFPToken::TSTATEMENTSEPARATOR ) {
				$this->doLevelSet( $result );
			}
		} while ( $this->mCur->type == AFPToken::TSTATEMENTSEPARATOR );
	}

	/**
	 * Handles multiple expressions
	 *
	 * @param $result AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function doLevelSet( &$result ) {
		if ( $this->mCur->type == AFPToken::TID ) {
			$varname = $this->mCur->value;
			$prev = $this->getState();
			$this->move();

			if ( $this->mCur->type == AFPToken::TOP && $this->mCur->value == ':=' ) {
				$this->move();
				$this->doLevelSet( $result );
				$this->setUserVariable( $varname, $result );

				return;
			} elseif ( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == '[' ) {
				if ( !$this->mVars->varIsSet( $varname ) ) {
					throw new AFPUserVisibleException( 'unrecognisedvar',
						$this->mCur->pos,
						array( $varname )
					);
				}
				$list = $this->mVars->getVar( $varname );
				if ( $list->type != AFPData::DLIST ) {
					throw new AFPUserVisibleException( 'notlist', $this->mCur->pos, array() );
				}
				$list = $list->toList();
				$this->move();
				if ( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == ']' ) {
					$idx = 'new';
				} else {
					$this->setState( $prev );
					$this->move();
					$idx = new AFPData();
					$this->doLevelSemicolon( $idx );
					$idx = $idx->toInt();
					if ( !( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == ']' ) ) {
						throw new AFPUserVisibleException( 'expectednotfound', $this->mCur->pos,
							array( ']', $this->mCur->type, $this->mCur->value ) );
					}
					if ( count( $list ) <= $idx ) {
						throw new AFPUserVisibleException( 'outofbounds', $this->mCur->pos,
							array( $idx, count( $result->data ) ) );
					}
				}
				$this->move();
				if ( $this->mCur->type == AFPToken::TOP && $this->mCur->value == ':=' ) {
					$this->move();
					$this->doLevelSet( $result );
					if ( $idx === 'new' ) {
						$list[] = $result;
					} else {
						$list[$idx] = $result;
					}
					$this->setUserVariable( $varname, new AFPData( AFPData::DLIST, $list ) );

					return;
				} else {
					$this->setState( $prev );
				}
			} else {
				$this->setState( $prev );
			}
		}
		$this->doLevelConditions( $result );
	}

	/**
	 * @param $result AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function doLevelConditions( &$result ) {
		if ( $this->mCur->type == AFPToken::TKEYWORD && $this->mCur->value == 'if' ) {
			$this->move();
			$this->doLevelBoolOps( $result );

			if ( !( $this->mCur->type == AFPToken::TKEYWORD && $this->mCur->value == 'then' ) ) {
				throw new AFPUserVisibleException( 'expectednotfound',
					$this->mCur->pos,
					array(
						'then',
						$this->mCur->type,
						$this->mCur->value
					)
				);
			}
			$this->move();

			$r1 = new AFPData();
			$r2 = new AFPData();

			$isTrue = $result->toBool();

			if ( !$isTrue ) {
				$scOrig = $this->mShortCircuit;
				$this->mShortCircuit = $this->mAllowShort;
			}
			$this->doLevelConditions( $r1 );
			if ( !$isTrue ) {
				$this->mShortCircuit = $scOrig;
			}

			if ( !( $this->mCur->type == AFPToken::TKEYWORD && $this->mCur->value == 'else' ) ) {
				throw new AFPUserVisibleException( 'expectednotfound',
					$this->mCur->pos,
					array(
						'else',
						$this->mCur->type,
						$this->mCur->value
					)
				);
			}
			$this->move();

			if ( $isTrue ) {
				$scOrig = $this->mShortCircuit;
				$this->mShortCircuit = $this->mAllowShort;
			}
			$this->doLevelConditions( $r2 );
			if ( $isTrue ) {
				$this->mShortCircuit = $scOrig;
			}

			if ( !( $this->mCur->type == AFPToken::TKEYWORD && $this->mCur->value == 'end' ) ) {
				throw new AFPUserVisibleException( 'expectednotfound',
					$this->mCur->pos,
					array(
						'end',
						$this->mCur->type,
						$this->mCur->value
					)
				);
			}
			$this->move();

			if ( $result->toBool() ) {
				$result = $r1;
			} else {
				$result = $r2;
			}
		} else {
			$this->doLevelBoolOps( $result );
			if ( $this->mCur->type == AFPToken::TOP && $this->mCur->value == '?' ) {
				$this->move();
				$r1 = new AFPData();
				$r2 = new AFPData();

				$isTrue = $result->toBool();

				if ( !$isTrue ) {
					$scOrig = $this->mShortCircuit;
					$this->mShortCircuit = $this->mAllowShort;
				}
				$this->doLevelConditions( $r1 );
				if ( !$isTrue ) {
					$this->mShortCircuit = $scOrig;
				}

				if ( !( $this->mCur->type == AFPToken::TOP && $this->mCur->value == ':' ) ) {
					throw new AFPUserVisibleException( 'expectednotfound',
						$this->mCur->pos,
						array(
							':',
							$this->mCur->type,
							$this->mCur->value
						)
					);
				}
				$this->move();

				if ( $isTrue ) {
					$scOrig = $this->mShortCircuit;
					$this->mShortCircuit = $this->mAllowShort;
				}
				$this->doLevelConditions( $r2 );
				if ( $isTrue ) {
					$this->mShortCircuit = $scOrig;
				}

				if ( $isTrue ) {
					$result = $r1;
				} else {
					$result = $r2;
				}
			}
		}
	}

	/**
	 * @param $result AFPData
	 */
	protected function doLevelBoolOps( &$result ) {
		$this->doLevelCompares( $result );
		$ops = array( '&', '|', '^' );
		while ( $this->mCur->type == AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$this->move();
			$r2 = new AFPData();

			// We can go on quickly as either one statement with | is true or on with & is false
			if ( ( $op == '&' && !$result->toBool() ) || ( $op == '|' && $result->toBool() ) ) {
				wfProfileIn( __METHOD__ . '-shortcircuit' );
				$orig = $this->mShortCircuit;
				$this->mShortCircuit = $this->mAllowShort;
				$this->doLevelCompares( $r2 );
				$this->mShortCircuit = $orig;
				$result = new AFPData( AFPData::DBOOL, $result->toBool() );
				wfProfileOut( __METHOD__ . '-shortcircuit' );
				continue;
			}

			$this->doLevelCompares( $r2 );

			$result = AFPData::boolOp( $result, $r2, $op );
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelCompares( &$result ) {
		$this->doLevelSumRels( $result );
		$ops = array( '==', '===', '!=', '!==', '<', '>', '<=', '>=', '=' );
		while ( $this->mCur->type == AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$this->move();
			$r2 = new AFPData();
			$this->doLevelSumRels( $r2 );
			if ( $this->mShortCircuit ) {
				break; // The result doesn't matter.
			}
			AbuseFilter::triggerLimiter();
			$result = AFPData::compareOp( $result, $r2, $op );
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelSumRels( &$result ) {
		$this->doLevelMulRels( $result );
		$ops = array( '+', '-' );
		while ( $this->mCur->type == AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$this->move();
			$r2 = new AFPData();
			$this->doLevelMulRels( $r2 );
			if ( $this->mShortCircuit ) {
				break; // The result doesn't matter.
			}
			if ( $op == '+' ) {
				$result = AFPData::sum( $result, $r2 );
			}
			if ( $op == '-' ) {
				$result = AFPData::sub( $result, $r2 );
			}
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelMulRels( &$result ) {
		$this->doLevelPow( $result );
		$ops = array( '*', '/', '%' );
		while ( $this->mCur->type == AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$this->move();
			$r2 = new AFPData();
			$this->doLevelPow( $r2 );
			if ( $this->mShortCircuit ) {
				break; // The result doesn't matter.
			}
			$result = AFPData::mulRel( $result, $r2, $op, $this->mCur->pos );
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelPow( &$result ) {
		$this->doLevelBoolInvert( $result );
		while ( $this->mCur->type == AFPToken::TOP && $this->mCur->value == '**' ) {
			$this->move();
			$expanent = new AFPData();
			$this->doLevelBoolInvert( $expanent );
			if ( $this->mShortCircuit ) {
				break; // The result doesn't matter.
			}
			$result = AFPData::pow( $result, $expanent );
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelBoolInvert( &$result ) {
		if ( $this->mCur->type == AFPToken::TOP && $this->mCur->value == '!' ) {
			$this->move();
			$this->doLevelSpecialWords( $result );
			if ( $this->mShortCircuit ) {
				return; // The result doesn't matter.
			}
			$result = AFPData::boolInvert( $result );
		} else {
			$this->doLevelSpecialWords( $result );
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelSpecialWords( &$result ) {
		$this->doLevelUnarys( $result );
		$keyword = strtolower( $this->mCur->value );
		if ( $this->mCur->type == AFPToken::TKEYWORD
			&& in_array( $keyword, array_keys( self::$mKeywords ) )
		) {
			$func = self::$mKeywords[$keyword];
			$this->move();
			$r2 = new AFPData();
			$this->doLevelUnarys( $r2 );

			if ( $this->mShortCircuit ) {
				return; // The result doesn't matter.
			}

			AbuseFilter::triggerLimiter();
			wfProfileIn( __METHOD__ . "-$func" );
			$result = AFPData::$func( $result, $r2, $this->mCur->pos );
			wfProfileOut( __METHOD__ . "-$func" );
		}
	}

	/**
	 * @param $result
	 */
	protected function doLevelUnarys( &$result ) {
		$op = $this->mCur->value;
		if ( $this->mCur->type == AFPToken::TOP && ( $op == "+" || $op == "-" ) ) {
			$this->move();
			$this->doLevelListElements( $result );
			if ( $this->mShortCircuit ) {
				return; // The result doesn't matter.
			}
			if ( $op == '-' ) {
				$result = AFPData::unaryMinus( $result );
			}
		} else {
			$this->doLevelListElements( $result );
		}
	}

	/**
	 * @param $result
	 * @throws AFPUserVisibleException
	 */
	protected function doLevelListElements( &$result ) {
		$this->doLevelBraces( $result );
		while ( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == '[' ) {
			$idx = new AFPData();
			$this->doLevelSemicolon( $idx );
			if ( !( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == ']' ) ) {
				throw new AFPUserVisibleException( 'expectednotfound', $this->mCur->pos,
					array( ']', $this->mCur->type, $this->mCur->value ) );
			}
			$idx = $idx->toInt();
			if ( $result->type == AFPData::DLIST ) {
				if ( count( $result->data ) <= $idx ) {
					throw new AFPUserVisibleException( 'outofbounds', $this->mCur->pos,
						array( $idx, count( $result->data ) ) );
				}
				$result = $result->data[$idx];
			} else {
				throw new AFPUserVisibleException( 'notlist', $this->mCur->pos, array() );
			}
			$this->move();
		}
	}

	/**
	 * @param $result
	 * @throws AFPUserVisibleException
	 */
	protected function doLevelBraces( &$result ) {
		if ( $this->mCur->type == AFPToken::TBRACE && $this->mCur->value == '(' ) {
			if ( $this->mShortCircuit ) {
				$this->skipOverBraces();
			} else {
				$this->doLevelSemicolon( $result );
			}
			if ( !( $this->mCur->type == AFPToken::TBRACE && $this->mCur->value == ')' ) ) {
				throw new AFPUserVisibleException(
					'expectednotfound',
					$this->mCur->pos,
					array( ')', $this->mCur->type, $this->mCur->value )
				);
			}
			$this->move();
		} else {
			$this->doLevelFunction( $result );
		}
	}

	/**
	 * @param $result
	 * @throws AFPUserVisibleException
	 */
	protected function doLevelFunction( &$result ) {
		if ( $this->mCur->type == AFPToken::TID && isset( self::$mFunctions[$this->mCur->value] ) ) {
			$func = self::$mFunctions[$this->mCur->value];
			$this->move();
			if ( $this->mCur->type != AFPToken::TBRACE || $this->mCur->value != '(' ) {
				throw new AFPUserVisibleException( 'expectednotfound',
					$this->mCur->pos,
					array(
						'(',
						$this->mCur->type,
						$this->mCur->value
					)
				);
			}

			if ( $this->mShortCircuit ) {
				$this->skipOverBraces();
				$this->move();

				return; // The result doesn't matter.
			}

			wfProfileIn( __METHOD__ . '-loadargs' );
			$args = array();
			do {
				$r = new AFPData();
				$this->doLevelSemicolon( $r );
				$args[] = $r;
			} while ( $this->mCur->type == AFPToken::TCOMMA );

			if ( $this->mCur->type != AFPToken::TBRACE || $this->mCur->value != ')' ) {
				throw new AFPUserVisibleException( 'expectednotfound',
					$this->mCur->pos,
					array(
						')',
						$this->mCur->type,
						$this->mCur->value
					)
				);
			}
			$this->move();

			wfProfileOut( __METHOD__ . '-loadargs' );

			wfProfileIn( __METHOD__ . "-$func" );

			$funcHash = md5( $func . serialize( $args ) );

			if ( isset( self::$funcCache[$funcHash] ) &&
				!in_array( $func, self::$ActiveFunctions )
			) {
				$result = self::$funcCache[$funcHash];
			} else {
				AbuseFilter::triggerLimiter();
				$result = self::$funcCache[$funcHash] = $this->$func( $args );
			}

			if ( count( self::$funcCache ) > 1000 ) {
				self::$funcCache = array();
			}

			wfProfileOut( __METHOD__ . "-$func" );
		} else {
			$this->doLevelAtom( $result );
		}
	}

	/**
	 * @param $result
	 * @throws AFPUserVisibleException
	 * @return AFPData
	 */
	protected function doLevelAtom( &$result ) {
		$tok = $this->mCur->value;
		switch ( $this->mCur->type ) {
			case AFPToken::TID:
				if ( $this->mShortCircuit ) {
					break;
				}
				$var = strtolower( $tok );
				$result = $this->getVarValue( $var );
				break;
			case AFPToken::TSTRING:
				$result = new AFPData( AFPData::DSTRING, $tok );
				break;
			case AFPToken::TFLOAT:
				$result = new AFPData( AFPData::DFLOAT, $tok );
				break;
			case AFPToken::TINT:
				$result = new AFPData( AFPData::DINT, $tok );
				break;
			case AFPToken::TKEYWORD:
				if ( $tok == "true" ) {
					$result = new AFPData( AFPData::DBOOL, true );
				} elseif ( $tok == "false" ) {
					$result = new AFPData( AFPData::DBOOL, false );
				} elseif ( $tok == "null" ) {
					$result = new AFPData();
				} else {
					throw new AFPUserVisibleException(
						'unrecognisedkeyword',
						$this->mCur->pos,
						array( $tok )
					);
				}
				break;
			case AFPToken::TNONE:
				return; // Handled at entry level
			case AFPToken::TBRACE:
				if ( $this->mCur->value == ')' ) {
					return; // Handled at the entry level
				}
			case AFPToken::TSQUAREBRACKET:
				if ( $this->mCur->value == '[' ) {
					$list = array();
					while ( true ) {
						$this->move();
						if ( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == ']' ) {
							break;
						}
						$item = new AFPData();
						$this->doLevelSet( $item );
						$list[] = $item;
						if ( $this->mCur->type == AFPToken::TSQUAREBRACKET && $this->mCur->value == ']' ) {
							break;
						}
						if ( $this->mCur->type != AFPToken::TCOMMA ) {
							throw new AFPUserVisibleException(
								'expectednotfound',
								$this->mCur->pos,
								array( ', or ]', $this->mCur->type, $this->mCur->value )
							);
						}
					}
					$result = new AFPData( AFPData::DLIST, $list );
					break;
				}
			default:
				throw new AFPUserVisibleException(
					'unexpectedtoken',
					$this->mCur->pos,
					array(
						$this->mCur->type,
						$this->mCur->value
					)
				);
		}
		$this->move();
	}

	/* End of levels */

	/**
	 * @param $var
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function getVarValue( $var ) {
		$var = strtolower( $var );
		$builderValues = AbuseFilter::getBuilderValues();
		if ( !( array_key_exists( $var, $builderValues['vars'] )
			|| $this->mVars->varIsSet( $var ) )
		) {
			// If the variable is invalid, throw an exception
			throw new AFPUserVisibleException(
				'unrecognisedvar',
				$this->mCur->pos,
				array( $var )
			);
		} else {
			return $this->mVars->getVar( $var );
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @throws AFPUserVisibleException
	 */
	protected function setUserVariable( $name, $value ) {
		$builderValues = AbuseFilter::getBuilderValues();
		if ( array_key_exists( $name, $builderValues['vars'] ) ) {
			throw new AFPUserVisibleException( 'overridebuiltin', $this->mCur->pos, array( $name ) );
		}
		$this->mVars->setVar( $name, $value );
	}



	// Built-in functions

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcLc( $args ) {
		global $wgContLang;
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'lc', 2, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		return new AFPData( AFPData::DSTRING, $wgContLang->lc( $s ) );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcUc( $args ) {
		global $wgContLang;
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'uc', 2, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		return new AFPData( AFPData::DSTRING, $wgContLang->uc( $s ) );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcLen( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'len', 2, count( $args ) )
			);
		}
		if ( $args[0]->type == AFPData::DLIST ) {
			// Don't use toString on lists, but count
			return new AFPData( AFPData::DINT, count( $args[0]->data ) );
		}
		$s = $args[0]->toString();

		return new AFPData( AFPData::DINT, mb_strlen( $s, 'utf-8' ) );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcSimpleNorm( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'simplenorm', 2, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		$s = preg_replace( '/[\d\W]+/', '', $s );
		$s = strtolower( $s );

		return new AFPData( AFPData::DSTRING, $s );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcSpecialRatio( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'specialratio', 1, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		if ( !strlen( $s ) ) {
			return new AFPData( AFPData::DFLOAT, 0 );
		}

		$nospecials = $this->rmspecials( $s );

		$val = 1. - ( ( mb_strlen( $nospecials ) / mb_strlen( $s ) ) );

		return new AFPData( AFPData::DFLOAT, $val );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcCount( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'count', 1, count( $args ) )
			);
		}

		if ( $args[0]->type == AFPData::DLIST && count( $args ) == 1 ) {
			return new AFPData( AFPData::DINT, count( $args[0]->data ) );
		}

		if ( count( $args ) == 1 ) {
			$count = count( explode( ',', $args[0]->toString() ) );
		} else {
			$needle = $args[0]->toString();
			$haystack = $args[1]->toString();

			// Bug #60203: Keep empty parameters from causing PHP warnings
			if ( $needle === '' ) {
				$count = 0;
			} else {
				$count = substr_count( $haystack, $needle );
			}
		}

		return new AFPData( AFPData::DINT, $count );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 * @throws Exception
	 */
	protected function funcRCount( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'rcount', 1, count( $args ) )
			);
		}

		if ( count( $args ) == 1 ) {
			$count = count( explode( ',', $args[0]->toString() ) );
		} else {
			$needle = $args[0]->toString();
			$haystack = $args[1]->toString();

			# Munge the regex
			$needle = preg_replace( '!(\\\\\\\\)*(\\\\)?/!', '$1\/', $needle );
			$needle = "/$needle/u";

			// Omit the '$matches' argument to avoid computing them, just count.
			$count = preg_match_all( $needle, $haystack );

			if ( $count === false ) {
				throw new AFPUserVisibleException(
					'regexfailure',
					$this->mCur->pos,
					array( 'unspecified error in preg_match_all()', $needle )
				);
			}
		}

		return new AFPData( AFPData::DINT, $count );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcIPInRange( $args ) {
		if ( count( $args ) < 2 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'ip_in_range', 2, count( $args ) )
			);
		}

		$ip = $args[0]->toString();
		$range = $args[1]->toString();

		$result = IP::isInRange( $ip, $range );

		return new AFPData( AFPData::DBOOL, $result );
	}

	/**
	 * @param $args
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcCCNorm( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'ccnorm', 1, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		$s = html_entity_decode( $s, ENT_QUOTES, 'UTF-8' );
		$s = $this->ccnorm( $s );

		return new AFPData( AFPData::DSTRING, $s );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcContainsAny( $args ) {
		if ( count( $args ) < 2 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'contains_any', 2, count( $args ) )
			);
		}

		$s = array_shift( $args );
		$s = $s->toString();

		$searchStrings = array();

		foreach ( $args as $arg ) {
			$searchStrings[] = $arg->toString();
		}

		if ( function_exists( 'fss_prep_search' ) ) {
			$fss = fss_prep_search( $searchStrings );
			$result = fss_exec_search( $fss, $s );

			$ok = is_array( $result );
		} else {
			$ok = false;
			foreach ( $searchStrings as $needle ) {
				// Bug #60203: Keep empty parameters from causing PHP warnings
				if ( $needle !== '' && strpos( $s, $needle ) !== false ) {
					$ok = true;
					break;
				}
			}
		}

		return new AFPData( AFPData::DBOOL, $ok );
	}

	/**
	 * @param $s
	 * @return mixed
	 */
	protected function ccnorm( $s ) {
		static $replacementArray = null;

		if ( is_null( $replacementArray ) ) {
			global $wgExtensionDirectory;

			if ( is_readable( "$wgExtensionDirectory/AntiSpoof/equivset.php" ) ) {
				// Satisfy analyzer.
				$equivset = null;
				// Contains a map of characters in $equivset.
				require "$wgExtensionDirectory/AntiSpoof/equivset.php";

				// strtr in ReplacementArray->replace() doesn't like this.
				if ( isset( $equivset[''] ) ) {
					unset( $equivset[''] );
				}

				$replacementArray = new ReplacementArray( $equivset );
			} else {
				// AntiSpoof isn't available, so just create a dummy
				wfDebugLog(
					'AbuseFilter',
					"Can't compute normalized string (ccnorm) as the AntiSpoof Extension isn't installed."
				);
				$replacementArray = new ReplacementArray( array() );
			}
		}

		return $replacementArray->replace( $s );
	}

	/**
	 * @param $s string
	 * @return array|string
	 */
	protected function rmspecials( $s ) {
		return preg_replace( '/[^\p{L}\p{N}]/u', '', $s );
	}

	/**
	 * @param $s string
	 * @return array|string
	 */
	protected function rmdoubles( $s ) {
		return preg_replace( '/(.)\1+/us', '\1', $s );
	}

	/**
	 * @param $s string
	 * @return array|string
	 */
	protected function rmwhitespace( $s ) {
		return preg_replace( '/\s+/u', '', $s );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcRMSpecials( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'rmspecials', 1, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		$s = $this->rmspecials( $s );

		return new AFPData( AFPData::DSTRING, $s );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcRMWhitespace( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'rmwhitespace', 1, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		$s = $this->rmwhitespace( $s );

		return new AFPData( AFPData::DSTRING, $s );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcRMDoubles( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'rmdoubles', 1, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		$s = $this->rmdoubles( $s );

		return new AFPData( AFPData::DSTRING, $s );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcNorm( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'norm', 1, count( $args ) )
			);
		}
		$s = $args[0]->toString();

		$s = $this->ccnorm( $s );
		$s = $this->rmdoubles( $s );
		$s = $this->rmspecials( $s );
		$s = $this->rmwhitespace( $s );

		return new AFPData( AFPData::DSTRING, $s );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcSubstr( $args ) {
		if ( count( $args ) < 2 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'substr', 2, count( $args ) )
			);
		}

		$s = $args[0]->toString();
		$offset = $args[1]->toInt();

		if ( isset( $args[2] ) ) {
			$length = $args[2]->toInt();

			$result = mb_substr( $s, $offset, $length );
		} else {
			$result = mb_substr( $s, $offset );
		}

		return new AFPData( AFPData::DSTRING, $result );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcStrPos( $args ) {
		if ( count( $args ) < 2 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'strpos', 2, count( $args ) )
			);
		}

		$haystack = $args[0]->toString();
		$needle = $args[1]->toString();

		// Bug #60203: Keep empty parameters from causing PHP warnings
		if ( $needle === '' ) {
			return new AFPData( AFPData::DINT, -1 );
		}

		if ( isset( $args[2] ) ) {
			$offset = $args[2]->toInt();

			$result = mb_strpos( $haystack, $needle, $offset );
		} else {
			$result = mb_strpos( $haystack, $needle );
		}

		if ( $result === false ) {
			$result = -1;
		}

		return new AFPData( AFPData::DINT, $result );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcStrReplace( $args ) {
		if ( count( $args ) < 3 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'str_replace', 3, count( $args ) )
			);
		}

		$subject = $args[0]->toString();
		$search = $args[1]->toString();
		$replace = $args[2]->toString();

		return new AFPData( AFPData::DSTRING, str_replace( $search, $replace, $subject ) );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function funcStrRegexEscape( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException( 'notenoughargs', $this->mCur->pos,
				array( 'rescape', 1, count( $args ) ) );
		}

		$string = $args[0]->toString();

		// preg_quote does not need the second parameter, since rlike takes
		// care of the delimiter symbol itself
		return new AFPData( AFPData::DSTRING, preg_quote( $string ) );
	}

	/**
	 * @param $args array
	 * @return mixed
	 * @throws AFPUserVisibleException
	 */
	protected function funcSetVar( $args ) {
		if ( count( $args ) < 2 ) {
			throw new AFPUserVisibleException(
				'notenoughargs',
				$this->mCur->pos,
				array( 'set_var', 2, count( $args ) )
			);
		}

		$varName = $args[0]->toString();
		$value = $args[1];

		$this->setUserVariable( $varName, $value );

		return $value;
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function castString( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException( 'noparams', $this->mCur->pos, array( __METHOD__ ) );
		}
		$val = $args[0];

		return AFPData::castTypes( $val, AFPData::DSTRING );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function castInt( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException( 'noparams', $this->mCur->pos, array( __METHOD__ ) );
		}
		$val = $args[0];

		return AFPData::castTypes( $val, AFPData::DINT );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function castFloat( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException( 'noparams', $this->mCur->pos, array( __METHOD__ ) );
		}
		$val = $args[0];

		return AFPData::castTypes( $val, AFPData::DFLOAT );
	}

	/**
	 * @param $args array
	 * @return AFPData
	 * @throws AFPUserVisibleException
	 */
	protected function castBool( $args ) {
		if ( count( $args ) < 1 ) {
			throw new AFPUserVisibleException( 'noparams', $this->mCur->pos, array( __METHOD__ ) );
		}
		$val = $args[0];

		return AFPData::castTypes( $val, AFPData::DBOOL );
	}
}
