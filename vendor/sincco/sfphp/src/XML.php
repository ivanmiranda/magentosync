<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.0.0
# -----------------------
# Manejo de archivos XML
# -----------------------

namespace Sincco\Sfphp;

final class XML extends \stdClass {
	public $data;

	public function __construct( $file ) {
		$this->data = $this->xmlstr_to_array( file_get_contents( $file ) );
	}

	function xmlstr_to_array($xmlstr) {
		$doc = new \DOMDocument();
		$doc->loadXML($xmlstr);
		$root = $doc->documentElement;
		$output = $this->domnode_to_array($root);
		// $output['@root'] = $root->tagName;
		return $output;
	}

	function domnode_to_array($node) {
		$output = array();
		switch ($node->nodeType) {
		case XML_CDATA_SECTION_NODE:
		case XML_TEXT_NODE:
		$output = trim($node->textContent);
		break;
		case XML_ELEMENT_NODE:
		for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
		$child = $node->childNodes->item($i);
		$v = $this->domnode_to_array($child);
		if(isset($child->tagName)) {
		$t = $child->tagName;
		if(!isset($output[$t])) {
		$output[$t] = array();
		}
		$output[$t][] = $v;
		}
		elseif($v || $v === '0') {
		$output = (string) $v;
		}
		}
		if($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
		$output = array('@content'=>$output); //Change output into an array.
		}
		if(is_array($output)) {
		if($node->attributes->length) {
		$a = array();
		foreach($node->attributes as $attrName => $attrNode) {
		$a[$attrName] = (string) $attrNode->value;
		}
		$output['@attributes'] = $a;
		}
		foreach ($output as $t => $v) {
		if(is_array($v) && count($v)==1 && $t!='@attributes') {
		$output[$t] = $v[0];
		}
		}
		}
		break;
		}
		return $output;
	}
}