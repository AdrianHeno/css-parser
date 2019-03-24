<?php
class CssParser
{
    public $css;
    public $parsed;
    public function loadString($string)
    {
        $this->css = $string;
    }

    public function loadFile($file)
    {
        $this->loadString(file_get_contents($file));
    }

    public function parse()
    {
        $css = $this->css;
        // Remove CSS-Comments
        $css = preg_replace('/\/\*.*?\*\//ms', '', $css);
        // Remove HTML-Comments
        $css = preg_replace('/([^\'"]+?)(\<!--|--\>)([^\'"]+?)/ms', '$1$3', $css);
        // Extract @media-blocks into $blocks
        preg_match_all('/@.+?\}[^\}]*?\}/ms', $css, $blocks);
        // Append the rest to $blocks
        array_push($blocks[0], preg_replace('/@.+?\}[^\}]*?\}/ms', '', $css));
        $ordered = array();
        for ($i=0; $i<count($blocks[0]); $i++) {
            // If @media-block, strip declaration and parenthesis
            if (substr($blocks[0][$i], 0, 6) === '@media') {
                $ordered_key = preg_replace('/^(@media[^\{]+)\{.*\}$/ms', '$1', $blocks[0][$i]);
                $ordered_value = preg_replace('/^@media[^\{]+\{(.*)\}$/ms', '$1', $blocks[0][$i]);
            }
            // Rule-blocks of the sort @import or @font-face
            elseif (substr($blocks[0][$i], 0, 1) === '@') {
                $ordered_key = $blocks[0][$i];
                $ordered_value = $blocks[0][$i];
            } else {
                $ordered_key = 'main';
                $ordered_value = $blocks[0][$i];
            }
            // Split by parenthesis, ignoring those inside content-quotes
            $ordered[$ordered_key] = preg_split('/([^\'"\{\}]*?[\'"].*?(?<!\\\)[\'"][^\'"\{\}]*?)[\{\}]|([^\'"\{\}]*?)[\{\}]/', trim($ordered_value, " \r\n\t"), -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
        }
        
        // Beginning to rebuild new slim CSS-Array
        foreach ($ordered as $key => $val) {
            $new = array();
            for ($i = 0; $i<count($val); $i++) {
                // Split selectors and rules and split properties and values
                $selector = trim($val[$i], " \r\n\t");
                
                if (!empty($selector)) {
                    if (!isset($new[$selector])) {
                        $new[$selector] = array();
                    }
                    $rules = explode(';', $val[++$i]);
                    foreach ($rules as $rule) {
                        $rule = trim($rule, " \r\n\t");
                        if (!empty($rule)) {
                            $rule = array_reverse(explode(':', $rule));
                            $property = trim(array_pop($rule), " \r\n\t");
                            $value = implode(':', array_reverse($rule));
                            
                            if (!isset($new[$selector][$property]) || !preg_match('/!important/', $new[$selector][$property])) {
                                $new[$selector][$property] = $value;
                            } elseif (preg_match('/!important/', $new[$selector][$property]) && preg_match('/!important/', $value)) {
                                $new[$selector][$property] = $value;
                            }
                        }
                    }
                }
            }
            $ordered[$key] = $new;
        }
        $this->parsed = $ordered;
    }

    public function glue()
    {
        if ($this->parsed) {
            $output = '';
            foreach ($this->parsed as $media => $content) {
                if (substr($media, 0, 6) === '@media') {
                    $output .= $media . " {\n";
                    $prefix = "\t";
                } else {
                    $prefix = "";
                }
                
                foreach ($content as $selector => $rules) {
                    $output .= $prefix.$selector . " {\n";
                    foreach ($rules as $property => $value) {
                        $output .= $prefix."\t".$property.': '.$value;
                        $output .= ";\n";
                    }
                    $output .= $prefix."}\n\n";
                }
                if (substr($media, 0, 6) === '@media') {
                    $output .= "}\n\n";
                }
            }
            return $output;
        }
    }
}
