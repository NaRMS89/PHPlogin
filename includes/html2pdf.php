<?php
class HTML2PDF {
    private $html;
    
    public function writeHTML($html) {
        $this->html = $html;
    }
    
    public function output() {
        // Since we can't rely on external libraries, we'll output the HTML directly
        // with PDF headers, which will cause the browser to attempt to render it as PDF
        header('Content-Type: application/pdf');
        echo $this->html;
    }
} 