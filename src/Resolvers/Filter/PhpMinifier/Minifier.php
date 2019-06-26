<?php

namespace marcocesarato\minifier;

/**
 * Minifier Class
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Minifier
 * @version 0.1.4
 */
class Minifier
{

    private $minificationStore = array();
    private $singleQuoteSequenceFinder;
    private $doubleQuoteSequenceFinder;
    private $blockCommentFinder;
    private $lineCommentFinder;

    /**
     * Minfier constructor.
     */
    public function __construct() {
        $this->singleQuoteSequenceFinder = new QuoteSequenceFinder('\'');
        $this->doubleQuoteSequenceFinder = new QuoteSequenceFinder('"');
        $this->blockCommentFinder = new StringSequenceFinder('/*', '*/');
        $this->lineCommentFinder = new StringSequenceFinder('//', "\n");
    }

    /**
     * Minify Javascript
     * @param $javascript
     * @return string
     */
    public function minifyJS($javascript) {
        $this->minificationStore = array();
        return self::minifyJSRecursive($javascript);
    }

    /**
     * Minify Javascript Recursive Function
     * @param $javascript
     * @return string
     */
    private function minifyJSRecursive($javascript) {

        $java_special_chars = array(
            $this->blockCommentFinder,// JavaScript Block Comment
            $this->lineCommentFinder,// JavaScript Line Comment
            $this->singleQuoteSequenceFinder,// single quote escape, e.g. :before{ content: '-';}
            $this->doubleQuoteSequenceFinder,// double quote
            new RegexSequenceFinder('regex', "/\(\h*(\/[\k\S]+\/)/") // JavaScript regex expression
        );
        // pull out everything that needs to be pulled out and saved
        while ($sequence = $this->getNextSpecialSequence($javascript, $java_special_chars)) {
            switch ($sequence->type) {
                case '/*':
                case '//':// remove comments
                    $javascript = substr($javascript, 0, $sequence->start_idx) . substr($javascript, $sequence->end_idx);
                    break;
                default: // quoted strings or regex that need to be preservered
                    $start_idx = ($sequence->type == 'regex' ? $sequence->sub_start_idx : $sequence->start_idx);
                    $end_idx = ($sequence->type == 'regex' ? $sequence->sub_start_idx + strlen($sequence->sub_match) : $sequence->end_idx);
                    $placeholder = $this->getNextMinificationPlaceholder();
                    $this->minificationStore[$placeholder] = substr($javascript, $start_idx, $end_idx - $start_idx);
                    $javascript = substr($javascript, 0, $start_idx) . $placeholder . substr($javascript, $end_idx);
            }
        }
        // special case where the + indicates treating variable as numeric, e.g. a = b + +c
        $javascript = preg_replace('/([-\+])\s+\+([^\s;]*)/', '$1 (+$2)', $javascript);
        // condense spaces
        $javascript = preg_replace("/\s*\n\s*/", "\n", $javascript); // spaces around newlines
        $javascript = preg_replace("/\h+/", " ", $javascript); // \h+ horizontal white space
        // remove unnecessary horizontal spaces around non variables (alphanumerics, underscore, dollarsign)
        $javascript = preg_replace("/\h([^A-Za-z0-9\_\$])/", '$1', $javascript);
        $javascript = preg_replace("/([^A-Za-z0-9\_\$])\h/", '$1', $javascript);
        // remove unnecessary spaces around brackets and parantheses
        $javascript = preg_replace("/\s?([\(\[{])\s?/", '$1', $javascript);
        $javascript = preg_replace("/\s([\)\]}])/", '$1', $javascript);
        // remove unnecessary spaces around operators that don't need any spaces (specifically newlines)
        $javascript = preg_replace("/\s?([\.=:\-+,])\s?/", '$1', $javascript);
        // unnecessary characters
        $javascript = preg_replace("/;\n/", ";", $javascript); // semicolon before newline
        $javascript = preg_replace('/;}/', '}', $javascript); // semicolon before end bracket
        // put back the preserved strings
        foreach ($this->minificationStore as $placeholder => $original) {
            $javascript = str_replace($placeholder, $original, $javascript);
        }

        return trim($javascript);
    }

    /**
     * Minify CSS
     * @param $css
     * @return string
     */
    public function minifyCSS($css) {
        $this->minificationStore = array();
        return self::minifyCSSRecursive($css);
    }

    /**
     * Minify CSS Recursive Function
     * @param $css
     * @return string
     */
    private function minifyCSSRecursive($css) {

        $css_special_chars = array(
            $this->blockCommentFinder,// CSS Comment
            $this->singleQuoteSequenceFinder,// single quote escape, e.g. :before{ content: '-';}
            $this->doubleQuoteSequenceFinder
        ); // double quote
        // pull out everything that needs to be pulled out and saved
        while ($sequence = $this->getNextSpecialSequence($css, $css_special_chars)) {
            switch ($sequence->type) {
                case '/*':// remove comments
                    $css = substr($css, 0, $sequence->start_idx) . substr($css, $sequence->end_idx);
                    break;
                default: // strings that need to be preservered
                    $placeholder = $this->getNextMinificationPlaceholder();
                    $this->minificationStore[$placeholder] = substr($css, $sequence->start_idx, $sequence->end_idx - $sequence->start_idx);
                    $css = substr($css, 0, $sequence->start_idx) . $placeholder . substr($css, $sequence->end_idx);
            }
        }
        // minimize the string
        $css = preg_replace('/\s{2,}/s', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        $css = preg_replace('/;}/', '}', $css);
        // put back the preserved strings
        foreach ($this->minificationStore as $placeholder => $original) {
            $css = str_replace($placeholder, $original, $css);
        }

        return trim($css);
    }

    /**
     * Minify HTML
     * @param $html
     * @return string
     */
    public function minifyHTML($html) {
        // In particolare gli <script src="..."></script> venivano eliminati perché al secondo giro $this->minificationStore veniva re-inizializzata
        $this->minificationStore = array();
        return self::minifyHTMLRecursive($html);
    }

    /**
     * Minify HTML Recursive Function
     * @param $html
     * @return string
     */
    private function minifyHTMLRecursive($html) {

        $html_special_chars = array(
            new RegexSequenceFinder('javascript', "/<\s*script(?:[^>]*)>(.*?)<\s*\/script\s*>/si"),
            // javascript, can have type attribute
            new RegexSequenceFinder('css', "/<\s*style(?:[^>]*)>(.*?)<\s*\/style\s*>/si"),
            // css, can have type/media attribute
            new RegexSequenceFinder('pre', "/<\s*pre(?:[^>]*)>(.*?)<\s*\/pre\s*>/si")
            // pre
        );
        // pull out everything that needs to be pulled out and saved
        while ($sequence = $this->getNextSpecialSequence($html, $html_special_chars)) {
            $placeholder = $this->getNextMinificationPlaceholder();
            $quote = substr($html, $sequence->start_idx, $sequence->end_idx - $sequence->start_idx);
            // subsequence (css/javascript/pre) needs special handeling, tags can still be minimized using minifyPHP
            $sub_start = $sequence->sub_start_idx - $sequence->start_idx;
            $sub_end = $sub_start + strlen($sequence->sub_match);
            switch ($sequence->type) {
                case 'javascript':
                    $quote = $this->minifyHTMLRecursive(substr($quote, 0, $sub_start)) . $this->minifyJSRecursive($sequence->sub_match) . $this->minifyHTMLRecursive(substr($quote, $sub_end));
                    break;
                case 'css':
                    $quote = $this->minifyHTMLRecursive(substr($quote, 0, $sub_start)) . $this->minifyCSSRecursive($sequence->sub_match) . $this->minifyHTMLRecursive(substr($quote, $sub_end));
                    break;
                default: // strings that need to be preserved, e.g. between <pre> tags
                    $quote = $this->minifyHTMLRecursive(substr($quote, 0, $sub_start)) . $sequence->sub_match . $this->minifyHTMLRecursive(substr($quote, $sub_end));
            }
            $this->minificationStore[$placeholder] = $quote;
            $html = substr($html, 0, $sequence->start_idx) . $placeholder . substr($html, $sequence->end_idx);
        }
        // condense white space
        $html = preg_replace(
            array('/\s+/u', '/<\s+/u', '/\s+>/u'),
            array(' ', '<', '>'),
            $html);
        // remove comments
        $html = preg_replace('/<!--[^\[](.*)[^\]]-->/Uuis', '', $html);
        // put back the preserved strings
        foreach ($this->minificationStore as $placeholder => $original) {
            $html = str_replace($placeholder, $original, $html);
        }

        return trim($html);
    }

    /**
     * Get next minification placeholder
     * @return string
     */
    private function getNextMinificationPlaceholder()
    {
        return '<-!!-' . sizeof($this->minificationStore) . '-!!->';
    }

    /**
     * Get next special sequence
     * @param $string
     * @param $sequences
     * @return bool|mixed
     */
    private function getNextSpecialSequence($string, $sequences) {
        $special_idx = array();
        foreach ($sequences as $finder) {
            $finder->findFirstValue($string);
            if ($finder->isValid()) {
                $special_idx[$finder->start_idx] = $finder;
            }
        }
        if (count($special_idx) == 0) {
            return false;
        }
        asort($special_idx);

        return $special_idx[min(array_keys($special_idx))];
    }
}

/**
 * Class MinificationSequenceFinder
 * @package marcocesarato\minifier
 */
abstract class MinificationSequenceFinder
{
    public $start_idx;
    public $end_idx;
    public $type;

    abstract protected function findFirstValue($string);

    public function isValid() {
        return $this->start_idx !== false;
    }
}

class RegexSequenceFinder extends MinificationSequenceFinder
{
    protected $regex;
    public $full_match;
    public $sub_match;
    public $sub_start_idx;

    function __construct($type, $regex) {
        $this->type = $type;
        $this->regex = $regex;
    }

    public function findFirstValue($string) {
        $this->start_idx = false; // reset
        preg_match($this->regex, $string, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches) > 0) {
            $this->full_match = $matches[0][0];
            $this->start_idx = $matches[0][1];
            if (count($matches) > 1) {
                $this->sub_match = $matches[1][0];
                $this->sub_start_idx = $matches[1][1];
            }
            $this->end_idx = $this->start_idx + strlen($this->full_match);
        }
    }
}

/**
 * Class QuoteSequenceFinder
 * @package marcocesarato\minifier
 */
class QuoteSequenceFinder extends MinificationSequenceFinder
{
    function __construct($type)
    {
        $this->type = $type;
    }

    public function findFirstValue($string){
        $this->start_idx = strpos($string, $this->type);
        if ($this->isValid()) {
            // look for first non escaped endquote
            $this->end_idx = $this->start_idx + 1;
            while ($this->end_idx < strlen($string)) {
                // find number of escapes before endquote
                if (preg_match('/(\\\\*)(' . preg_quote($this->type) . ')/', $string, $match, PREG_OFFSET_CAPTURE, $this->end_idx)) {
                    $this->end_idx = $match[2][1] + 1;
                    // if odd number of escapes before endquote, endquote is escaped. Keep going
                    if (!isset($match[1][0]) || strlen($match[1][0]) % 2 == 0) {
                        return;
                    }
                } else {// no match, not well formed
                    $this->end_idx = strlen($string);

                    return;
                }
            }
        }
    }
}

/**
 * Class StringSequenceFinder
 * @package marcocesarato\minifier
 */
class StringSequenceFinder extends MinificationSequenceFinder
{
    protected $start_delimiter;
    protected $end_delimiter;

    function __construct($start_delimiter, $end_delimiter) {
        $this->type = $start_delimiter;
        $this->start_delimiter = $start_delimiter;
        $this->end_delimiter = $end_delimiter;
    }

    public function findFirstValue($string) {
        $this->start_idx = strpos($string, $this->start_delimiter);
        if ($this->isValid()) {
            $this->end_idx = strpos($string, $this->end_delimiter, $this->start_idx + 1);
            // sanity check for non well formed lines
            $this->end_idx = ($this->end_idx === false ? strlen($string) : $this->end_idx + strlen($this->end_delimiter));
        }
    }
}