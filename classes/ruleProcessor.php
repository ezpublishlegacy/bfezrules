<?php
/**
 * Rule reader and processor
 */
class ruleProcessor {
	private $corePath = "";
	function __construct() {
		$this->corePath = dirname(__FILE__)."/"."../../../";
		self::prepareGlobalBucketStorage();
	}
	
	function process($ruleSetName) {
	}
	
	function processRuleSet($ruleSetName, $nodeObj, $returnAsXMLNodes = false) {
//		print "XMLFILE LIST for $ruleSetName = \n";
		// figure out which ruleSet you're processing
		$bfEZRuleIni = eZINI::instance("bfezrules.ini");
		$xmlFileList = $bfEZRuleIni->variable("RuleSet_$ruleSetName", "RuleSet"); // that should yield a simple array of XML files

		// load up all the xml files associated
		$fullActionArr = array();
		foreach ($xmlFileList as $xmlFile) {
			$actionArr = $this->processXMLFile($xmlFile, $nodeObj);
			$fullActionArr = array_merge($fullActionArr, $actionArr);
		}
		if ($returnAsXMLNodes) {
			return($fullActionArr);
		}
		// let's convert those into an array, actually
		$returnArray = array();
		foreach ($fullActionArr as $actionXMLNode) {
			array_push($returnArray, $this->convertActionNodeToPHP($actionXMLNode));
		}
		return($returnArray);
	}

	function processXMLFile($xmlFile, $nodeObj) {
		$actionArr = array();
		// read the XML file, process individual rules
		$fullXMLFilePath = $this->corePath.$xmlFile;
		$cleanedUpPath = realpath($fullXMLFilePath); // need for display
		if (!file_exists($fullXMLFilePath)) {
			die("No XML FILE $fullXMLFilePath!");
		}
		
		$xmlDoc = new DOMDocument();
		$xmlDoc->load($fullXMLFilePath);
		$rootNode = $xmlDoc->getElementsByTagName("rules")->item(0);
		$ruleCounter = 0;
		for ($i=0; $i<$rootNode->childNodes->length; $i++) {
			$ruleXMLNode = $rootNode->childNodes->item($i);
			if (!($ruleXMLNode instanceof DOMText)) {
				$tagType = $ruleXMLNode->tagName;
				if ($tagType == "include") {
	//				$this->includeNewFile();
				} else if ($tagType == "rule") {
					$ruleCounter++;
					$isRuleSatisfied = $this->isRuleSatisfied($nodeObj, $ruleXMLNode, $cleanedUpPath, $ruleCounter);
					if ($isRuleSatisfied) {
						$actionArr = array_merge($actionArr, $this->extractActionsFromNode($ruleXMLNode)); 
					}
				}
			}
		}
		return($actionArr);
	}

	function isRuleSatisfied($nodeObj, DOMElement $ruleXMLNode, $fullXMLFilePath, $ruleCounter) {
		$ruleName = $ruleXMLNode->hasAttribute("name") ? $ruleXMLNode->getAttribute("name") : "";
		
		// look for subnode called "logic"
		$logicNodes = $ruleXMLNode->getElementsByTagName("logic");
		if ($logicNodes->length == 0) {
			return(false);
		}
		$logicNode = $logicNodes->item(0);
		$logicCode = $logicNode->textContent;
		// add a variable to be executed every time - this will tell us if the eval failed or not
		unset($BFRuleProcessorProcessedSomething);
$processLogicData = <<<EOLOGIC
	\$BFRuleProcessorProcessedSomething = false;
	\$bucketNames = self::getBucketNames();
	foreach (\$bucketNames as \$bucketName) {
		$\$bucketName = self::getBucket(\$bucketName);
	}
	$logicCode
EOLOGIC;
		unset($result);
		@eval($processLogicData);
		if (!isset($BFRuleProcessorProcessedSomething)) {
			print "PROBLEM PROCESSING CODE IN FILE $fullXMLFilePath";
			if ($ruleName != "") {
				print ", in rule \"$ruleName\":\n";
			} else {
				print ", in an unnamed rule (#{$ruleCounter} from the top):\n";
			}
			print "<pre>\n$processLogicData\n</pre>\n";
		}
		if (!isset($result) || !is_bool($result)) {
			return(false);
		}
		return($result);
	}
	
	function extractActionsFromNode (DOMElement $ruleXMLNode) {
		$actionArr = array();
		// look for all subnodes called "action"
		$actionNodes = $ruleXMLNode->getElementsByTagName("action");
		for ($i=0; $i<$actionNodes->length; $i++) {
			$actionNode = $actionNodes->item($i);
			array_push($actionArr, $actionNode);
		}
		return($actionArr);
	}
	
	function convertActionNodeToPHP (DOMElement $actionXMLNode) {
		$returnHash = array();
		$actionName = $actionXMLNode->hasAttribute("name") ? $actionXMLNode->getAttribute("name") : "";
		$allAttributeCollection = $actionXMLNode->attributes;
		foreach ($allAttributeCollection as $attrName => $attrValue) {
			$returnHash[$attrName] = $attrValue->value;
		}
		return($returnHash);
	}

	
	////////////////////////////////////////////////////////////////////////////////
	//// Bucket storage needed for all logic, data shared between pieces of logic
	////////////////////////////////////////////////////////////////////////////////
	
	static function prepareGlobalBucketStorage() {
		$GLOBALS["ruleDataHash"] = array();
	}
	
	static function getBucketNames() {
		return(array_keys($GLOBALS["ruleDataHash"]));
	}
	
	static function getBucket($groupName) {
		if (array_key_exists($groupName, $GLOBALS["ruleDataHash"])) {
			return($GLOBALS["ruleDataHash"][$groupName]);
		}
		return(null);
	}
	
	static function setBucket($groupName, $groupValue) {
		$GLOBALS["ruleDataHash"][$groupName] = $groupValue;
	}
	
	static function setBucketVar($groupName, $varName, $value) {
		if (array_key_exists($groupName, $GLOBALS["ruleDataHash"])) {
			if (is_array($groupName, $GLOBALS["ruleDataHash"])) {
				$GLOBALS["ruleDataHash"][$groupName][$varName] = $value;
			}
		}
	}
}
?>