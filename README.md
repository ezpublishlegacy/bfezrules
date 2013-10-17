bfEzRules eZPublish extension
=============================

Purpose: to add the ability to process generic rule lists, producing a set of "actions" coming out of that.
You can also think about this as override that

1. can be applied anywhere
2. works with full Boolean logic

See examples below.

Practical applications possible:
--------------------------------
1. to determine/limit the child creation choices based on specific project needs. (see bfchildlimitation extension)
2. to determine/limit block availability based on override-like rules (TODO: bfextendedblock)
3. to determine/limit options used in blocks (TODO: bfextendedblock)

Multilanguage considerations:
--------------------------------
None.

Multisite considerations:
--------------------------------
None.

Setup
--------------------------------
Just add as extension. 
In your code, call either of the provided functions. 

1. ruleProcessor->processRuleSet($ruleSetName, $nodeObj, $returnAsXML) - this call operates to compute actions based on a ruleset, within the context of a given node

2. ruleProcessor->processXMLFile($xmlFile, $nodeObj) - just runs the rules and collects actions from a single xml file. XML file is really a part relative to the ez root directory.

How to define rule sets:

In bfezrules.ini, have a following structure:

```
[RuleSet_SetName]
RuleSet[]
RuleSet[]=extension/bfezrules/settings/standardVariableGather.xml
RuleSet[]=extension/{yourextension}/settings/standardExclusions-YourSite.xml
```

These files will be processed one by one, and you'll get a list of action XML nodes in the end.

XML File Examples
---------------------------------
Please take a look at extension bfchildlimitation, file standardExclusions-Multisite.xml
When that extension is activated, it will cleanse the admin user of stupid actions (like the ability to add non-user objects in the user section).