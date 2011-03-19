<?php

/**
 * @desc mPrint Class File
 *
 * @author Morris Jencen O. Chavez <macinville@gmail.com>
 * @version 0.0.3
 * @license http://www.opensource.org/licenses/mit-license.php MIT license

 *
 * @desc mPrint prints the desired elements in your HTML page.
 * Check out @link http://www.bennadel.com/blog/1591-Ask-Ben-Print-Part-Of-A-Web-Page-With-jQuery.htm for details.
 *
 * Installation:
 *
 * 1) Extract files under extensions directory
 *
 * Upgrading:
 *
 * 1) Replace the mPrint folder with this one
 * 2) Delete all the contents under the application's assets folder (the one outside the protected folder)
 * 
 * @example
 *
 * <?php
 *      $this->widget('ext.mPrint.mPrint', array(
 *           'title' => 'title',        //the title of the document. Defaults to the HTML title
 *           'tooltip' => 'print page results',    //tooltip message of the print icon. Defaults to 'print'
 *           'text' => 'Print Results', //text which will appear beside the print icon. Defaults to NULL
 *           'element' => '#page',      //the element to be printed.
 *           'exceptions' => array(     //the element/s which will be ignored
 *               '.summary',
 *               '.search-form'
 *           ),
 *           'publishCss' => true,       //publish the CSS for the whole page?
 *			 'visible' => Yii::app()->user->checkAccess('print')	//should this be visible to the current user?
 *			 'alt' => 'print'			//text which will appear if image can't be loaded
 *       ));
 * ?>
 *
 * Changelogs:
 * 0.0.6
 *  - added the property 'alt' (string), which is the text that will appear if the
 *      image is not available
 *  - modified the way the property is being checked from isset() to strlen().
 *  - enhanced the way in producing the image tag and used the Yii-way instead of manually creating it.
 *  - enhanced (somehow) the documentation.
 * 0.0.5
 *  - added the property 'showIcon' (bool), which tells whether to include the
 *      image in the link or not
 *  - added the property 'image' (string), which tells the widget what icon to use.
 *      With this, different icons can be used in different widget calls (for example,
 *      one grid icon to print the datagrid results, and one calendar icon to print
 *      the calendar div). It will only be rendered if the properties 'visible' and 'showIcon' are
 *      set to 'true'. Default value is 'printer.png'
 * 0.0.4
 *  - added the property 'visible' (bool), which dicates the visibility of the printing link
 *  - modified some lines of codes to make it more clearer
 * 0.0.3
 *  - fixed the way the JS file creates the iFrame (the <head> is inside the <body>)
 *  - added the execCommand in mPrint.js to fix IE7 and IE8 issue on printing banners
 * 0.0.2
 *  - fixed the bug 'Missing argument 2 for CClientScript::registerCss()' by replacing registerCss with registerCssFile (thanks to joblo)
 *  - modified the property 'exemptions' to 'exceptions' for a more definitive term (thanks Gustavo)
 *  - added the property 'publishCss' bool, which will dictate whether to register the CSS file for the whole page (for the benefit of CTRL+P).
 * 0.0.1
 *  - initial release
 */
class mPrint extends CWidget {

    /**
     * @var string Path of the asset files after publishing.
     */
    private $assetsPath;
    /**
     * @var string Icon link to be used for printing. This will only be used
     * if $visible is set to true.
     * @since 0.0.5
     */
    private $printerIcon;
    /**
     * @var string The css file which will be used by the printed document.
     * Default is 'mprint.css'.
     */
    public $css = 'mprint.css';
    /**
     * @var string Title of the document to be printed. Defaults to the title
     * of the HTML.
     */
    public $title = NULL;
    /**
     * @var string Tooltip message for the print icon. Defaults to "print".
     */
    public $tooltip = "print";
    /**
     * @var string Message which will appear beside the print icon.
     */
    public $text = "";
    /**
     * @var array Yii-standard variable.
     */
    public $htmlOptions = array();
    /**
     * @var string HTML element (div or class) which will be printed.
     * Defaults to '#page'.
     */
    public $element = '#page';
    /**
     * @var array HTML elements which will be exempted in printing.
     * Use @link http://api.jquery.com/category/selectors/ jQuery-selector
     */
    public $exceptions = array();
    /**
     * @var bool Whether to register the CSS file for the whole page (for the
     * benefit of CTRL+P).
     * Defaults to false.
     * @since 0.0.2 
     */
    public $publishCss = false;
    /**
     * @var bool Sets the visibility of the print link. Defaults to true.
     * @since 0.0.4
     */
    public $visible = true;
    /**
     * @var bool Whether to show the icon or not. Defaults to true.
     * @since 0.0.5
     */
    public $showIcon = true;
    /**
     * @var string The image/icon. This will only be visible if $showIcon and
     * $visible are set to true. Defaults to 'printer.png'.
     * @since 0.0.5
     */
    public $image = 'printer.png';
    /**
     * @var string Alternative text to displahy if no image is available.
     * This will only be used if $showIcon and $visible are set to true.
     * Defaults to "".
     * @since 0.0.6
     */
    public $alt = "";


    public function init() {
        $assets = dirname(__FILE__) . '/' . 'assets';
        $this->assetsPath = Yii::app()->getAssetManager()->publish($assets);
        $this->printerIcon = $this->assetsPath . '/' . $this->image;
        Yii::app()->getClientScript()->registerScriptFile($this->assetsPath . '/' . 'mPrint.js');
        Yii::app()->clientScript->registerCoreScript('jquery');

        //to publish or not to publish? that is the question
        $this->publishCss ? Yii::app()->getClientScript()->registerCssFile($this->assetsPath . '/' . $this->css, "print") : '';
    }

    public function run() {
        //only make the effort if the link should be visible, and if there's something to
        // display (like 'text' should be defined if 'showIcon' is false.)
        if ($this->visible && (strlen(trim($this->text))>0 || $this->showIcon)) {
            //display the print icon
            $this->showPrintLink();

            //add the appropriate class which will be printed and ignored
            $this->addApprClass();

            // hook the event to the print icon
            $this->mPrint();

            //set some css...
            $this->someCss();
        }
    }

    /**
     * @desc renders the link for printing the page
     */
    private function showPrintLink() {
        // Evaluate if the text should be displayed. If spaces are given, it is as
        // good as nothing
        $text = (strlen(trim($this->text)) > 0) ? $text = "&nbsp;" . $this->text : '';

        // Should the icon be displayed?
        $img = $this->showIcon ? CHtml::image($this->printerIcon, $this->alt, array('title' => $this->tooltip)) : '';

        echo CHtml::link($img . $text, "", array_merge($this->htmlOptions, array('id' => 'mprint')));
    }

    /**
     * @desc adds the appropriate classes for included and ignored elements
     */
    private function addApprClass() {
        //add the class "mprint" to those elements which should be printed
        Yii::app()->clientScript->registerScript('addPrintClass', '
                $(function(){
                    $("' . $this->element . '").addClass("mprint");
                });
            ');

        //hide the elements which should not be printed (if any)
        if (count($this->exceptions)) {
            $hideElements = "";
            foreach ($this->exceptions as $index => $exemption)
                $hideElements .= '$("' . $exemption . '").addClass("hide-print");';
            //add the appropriate class which will be printed
            Yii::app()->clientScript->registerScript('hidePrintClass', '
                $(function(){
                    ' . $hideElements . '
                });
            ');
        }
    }

    /**
     * @desc the one calling our js file
     */
    public function mPrint() {
        //set the file name. Defaults to the title of the HTML
        $mac = (isset($this->title) && strlen($this->title)>0) ?
                'var documentName = "' . $this->title . '";' :
                'var documentName = document.title;';

        //give the link to the CSS file to be used by the report
        $mac .= 'var css="' . $this->assetsPath . '/' . $this->css . '";';

        //register the script
        Yii::app()->clientScript->registerScript('processPrint', '
            $(function(){
                // Hook up the print link.
                $( "#mprint" )
                .attr( "href", "javascript:void( 0 )" )
                .click(
                function(){
                    ' . $mac . '
                    // Print the DIV.
                    $( ".mprint" ).print(documentName,css);

                    // Cancel click event.
                    return( false );
                });
            });'
        );
    }

    /**
     * @desc some CSS...
     */
    private function someCss() {
        //hide those which should be ignored
        echo CHtml::css(".hide-print {display: none;}", "print");
        //cursor type for the generated printer link
        echo CHtml::css("#mprint {cursor: 'pointer';}", "screen");
    }

}

?>
