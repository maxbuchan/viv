<?php
/**
 * SpecialMobileEditor.php
 */

/**
 * Provides a special page to redirect to the editor of an article page
 */
class SpecialMobileEditor extends MobileSpecialPage {
	/**
	 * Construct function
	 */
	public function __construct() {
		parent::__construct( 'MobileEditor' );
		$this->listed = false;
	}

	/**
	 * Render the special page and redirect the user to the editor (if page exists)
	 * @param string $subpage The name of the page to edit
	 */
	public function executeWhenAvailable( $subpage ) {
		if ( !is_string( $subpage ) ) {
			$this->showPageNotFound();
			return;
		} else {
			$title = Title::newFromText( $subpage );
			if ( is_null( $title ) ) {
				$this->showPageNotFound();
				return;
			}
		}

		$data = $this->getRequest()->getValues();
		unset( $data['title'] ); // Remove the title of the special page

		$section = (int)$this->getRequest()->getVal( 'section', 0 );

		$output = $this->getOutput();
		$output->addModules( 'mobile.special.mobileeditor.scripts' );
		$output->setPageTitle( $this->msg( 'mobile-frontend-editor-redirect-title' )->text() );

		$context = MobileContext::singleton();
		$articleUrl = $context->getMobileUrl( $title->getFullURL( $data ) );
		$targetUrl = $articleUrl . '#/editor/' . $section;

		$html =
			Html::openElement( 'div',
				[
					'id' => 'mw-mf-editor',
					'data-targeturl' => $targetUrl
				]
			) .
			Html::openElement( 'noscript' ) .
			MobileUI::errorBox( $this->msg( 'mobile-frontend-editor-unavailable' )->text() ) .
			Html::openElement( 'p' ) .
				Html::element( 'a',
					[ 'href' => $title->getLocalUrl() ],
					$this->msg( 'returnto', $title->getText() )->text() ) .
			Html::closeElement( 'noscript' ) .
			Html::closeElement( 'div' ); // #mw-mf-editorunavailable

		$output->addHTML( $html );
	}
}
