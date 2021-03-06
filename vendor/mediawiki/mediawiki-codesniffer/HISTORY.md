# MediaWiki-Codesniffer release history #

## 18.0.0 / 2018-04-13 ##
* Add common autofix replacements for invalid license tag sniff (Kunal Mehta)
* Add test for 'You must use "/**" style comments for a function' (Thiemo Kreuz)
* Allow @dataProvider annotations in traits (Thiemo Kreuz)
* Automatically replace DO_MAINTENANCE (Kunal Mehta)
* Don't report forbidden tags as "should be used inside test classes" (Thiemo Kreuz)
* Don't require documenting self-explaining parameter-less functions (Thiemo Kreuz)
* Fix IllegalSingleLineComment sniff fix for unclosed comments (Thiemo Kreuz)
* Make unused global variables sniff much more robust (Thiemo Kreuz)
* Minor performance optimizations to the UnusedUseStatement sniff (Thiemo Kreuz)
* Optimize PHPUnitClassUsage sniff for performance (Thiemo Kreuz)
* Optimize PrefixedGlobalFunctions sniff for performance (Thiemo Kreuz)
* Optimize ShortCastSyntax sniff for performance (Thiemo Kreuz)
* Scan for return tags from the end of the function scope (Thiemo Kreuz)
* Shorten out earlier in the DbrQueryUsage sniff (Thiemo Kreuz)
* Shorten out earlier in the FunctionComment sniff (Thiemo Kreuz)

## 17.0.0 / 2018-03-28 ##
* Allow globals to start with numbers (Umherirrender)
* Check for close parenthesis first and shorten out earlier (Thiemo Kreuz)
* Check for use statement with non-compound name (Umherirrender)
* Don't require @covers from abstract tests (Max Semenik)
* Forbid usage of assert() (Kunal Mehta)
* Improve ClassMatchesFilenameSniff::isMaintenanceScript (Umherirrender)
* Improve performance of PrefixedGlobalFunctionsSniff (Umherirrender)
* Optimize ClassMatchesFilename sniff for performance (Thiemo Kreuz)
* Optimize DirUsage sniff for performance (Thiemo Kreuz)
* Optimize MultipleEmptyLines sniff for performance (Thiemo Kreuz)
* Remove warning about "missing" @param comments (Thiemo Kreuz)
* Simplify UnusedGlobalVariables sniff (Thiemo Kreuz)
* Skip closing parentheses in "( )" and "[ ]" instead of rechecking (Thiemo Kreuz)
* Skip empty () and [], not processing closing token a second time (Thiemo Kreuz)
* Update squizlabs/php_codesniffer to 3.2.3 (Kunal Mehta)
* Use faster array_key_exists instead of in_array in 2 sniffs (Thiemo Kreuz)
* Use faster strcasecmp() instead of strtolower() for comparisons (Thiemo Kreuz)
* Use File::findExtendedClassName to get extends name (Umherirrender)
* Use File::getDeclarationName to get the function name (Umherirrender)
* Use File::getMethodProperties to get visibility (Umherirrender)
* Validate @license against SPDX (Umherirrender)
* Validate phpunit annotations (Umherirrender)

## 16.0.1 / 2018-02-24 ##
* Fix SpaceBeforeControlStructureBraceSniff (Thiemo Kreuz)
* Grade errors about duplicate spaces in PHPDoc tags down to warnings (Thiemo Kreuz)
* Remove unused code and function arguments from three sniffs (Thiemo Kreuz)
* Replace substr_compare with substr (Umherirrender)
* Streamline SpaceBeforeControlStructureBraceSniff implementation (Thiemo Kreuz)

## 16.0.0 / 2018-02-14 ##
* Add sniff to find tests without @covers tags (Kunal Mehta)
* Add sniff to use namespaced PHPUnit\Framework\TestCase (Kunal Mehta)
* Avoid assignment in return statements (Umherirrender)
* Be aware of extension MediaWiki compatibility (Kunal Mehta)
* Detect variadic arguments in function comments (Umherirrender)
* Disallow PHP 7.2+ `object` type-hint (Kunal Mehta)
* Downgrade "Missing parameter comment" to warning (Reedy)
* Expand sniff to replace some php aliases with main function (Umherirrender)
* Fix SpaceyParenthesisSniff comment detection for ignore statements (Umherirrender)
* Fix Undefined index: scope_opener in IfElseStructureSniff (Umherirrender)
* Forbid parse_str() without a second argument (Umherirrender)
* Remove direction from @param (Umherirrender)
* Remove unneeded closing declaration comments (Umherirrender)
* Remove unneeded @codingStandardsIgnoreLine from test (Umherirrender)
* Skip __construct on checking for @return tags (Umherirrender)
* Use SPDX 3.0 license identifier (Kunal Mehta)
* Warn on usage of each() (Kunal Mehta)

## 15.0.0 / 2017-12-29 ##
* Add sniff for using is_int over is_integer (Kunal Mehta)
* Allow _ in unit test method names (Gerg?? Tisza)
* Check function definitions for the same variable name (Kunal Mehta)
* Fix handling of alternative if in IfElseStructureSniff (Umherirrender)
* Forbid usage of extract() (Kunal Mehta)
* Ignore maintenance scripts in ClassMatchesFilenameSniff (Kunal Mehta)
* Improve PHPDoc classname parsing (Gerg?? Tisza)
* Move phpcs.xml to .phpcs.xml (Umherirrender)
* Remove WhiteSpace.SpaceBeforeSingleLineComment.EmptyComment (Gerg?? Tisza)
* Replace PEAR with Packagist in README.md link (Ricordisamoa)
* Require that an explicit visiblity is set on methods and properties (Kunal Mehta)
* Rework ExtendClassUageSniff to avoid private class member (Umherirrender)
* Skip inner functions in FunctionCommentSniff::processReturn (Umherirrender)
* Update PHP_CodeSniffer to 3.2.2 (Ricordisamoa, Kunal Mehta)
* Use backticks in HISTORY.md (Ricordisamoa)
* Use only PSR2.Files.EndFileNewline (Kunal Mehta)
* Use upstream Generic.Files.OneObjectStructurePerFile sniff (Kunal Mehta)
* Use upstream Generic.PHP.DiscourageGoto (Kunal Mehta)
* Warn on usage of create_function() (Kunal Mehta)

## 14.1.0 / 2017-10-20 ##
* Update PHP_CodeSniffer to 3.1.1 (Paladox)

## 14.0.0 / 2017-10-20 ##
* Add sniff for @params instead of @param (Umherirrender)
* Better distinguish "one space before brace" and "brace on same line" (Florian Schmidt)
* Typo fix in docs (MarcoAurelio)
* Unwrap types in function docs from {} (Umherirrender)
* Update PHP_CodeSniffer to 3.1.0 from 3.0.2 (Paladox)
* Validate doc syntax (Umherirrender)

## 13.0.0 / 2017-09-23 ##
* Add sniff for @cover instead of @covers (James D. Forrester)
* Add sniff to find and replace deprecated constants (Kunal Mehta)
* Add sniff to find unused "use" statements (Kunal Mehta)
* Add space after keyword require_once, if needed (Umherirrender)
* Fix @returns and @throw in function docs (Umherirrender)
* Prohibit some globals (Max Semenik)
* Skip function comments with @deprecated (Umherirrender)
* Sniff & fix lowercase @inheritdoc (Gerg?? Tisza)

## 0.12.0 / 2017-08-29 ##
* Add sniff to ensure floats have a leading `0` if necessary (Kunal Mehta)
* Add sniff to ensure the class name matches the filename (Kunal Mehta)
* Change bootstrap-ci.php to match PHP CodeSniffer 3.0.0 (Umherirrender)
* Check for unneeded punctation in @param and @return (Umherirrender)
* Check spacing after type in @return (Umherirrender)
* Check spacing before type in @param and @return (Umherirrender)
* Clean up test helpers (Kunal Mehta)
* Do not mess long function comments on composer fix (Umherirrender)
* Enforce "short" type definitions in multi types in function comments (Umherirrender)
* Make it easier to figure out which test failed (Kunal Mehta)
* phpunit: replace deprecated strict=true (Umherirrender)
* Remove GoatSniffer integration (Kunal Mehta)
* Remove unmatched @codingStandardsIgnoreEnd (Umherirrender)
* Rename OpeningKeywordBracketSniff to OpeningKeywordParenthesisSniff (Reedy)
* Use local OneClassPerFile sniff for only one class/interface/trait (Kunal Mehta)

## 0.11.1 / 2017-08-13 ##
* Add GoatSniffer ASCII art (Kunal Mehta)

## 0.11.0 / 2017-08-10 ##
* Added OpeningKeywordBraceSniff (Umherirrender)
* Add sniff to forbid PHP 7 scalar type hints (Kunal Mehta)
* Enable Squiz.WhiteSpace.OperatorSpacing (Umherirrender)
* Enforce "short" type definitions on @param in comments (Umherirrender)
* Fix phpunit test on windows (Umherirrender)
* Fix Undefined offset in FunctionCommentSniff (Umherirrender)

## 0.10.1 / 2017-07-22 ##
* Add .gitattributes (Umherirrender)
* Add Squiz.Classes.SelfMemberReference to ruleset (Kunal Mehta)
* build: Added php-console-highlighter (Umherirrender)
* Don't ignore files or paths with "git" in them, only .git (Kunal Mehta)
* Fix exclude of common folders (Umherirrender)
* Fix "Undefined index: scope_opener" in SpaceBeforeClassBraceSniff (Reedy)
* Forbid backtick operator (Matthew Flaschen)
* Ignore returns in closures for MissingReturn sniff (Kunal Mehta)
* PHP CodeSniffer on CI should only lint HEAD (Antoine Musso)
* Reduce false positives in ReferenceThisSniff (Kunal Mehta)
* Sniff that the short type form is used in @return tags (Kunal Mehta)
* Swap isset() === false to !isset() (Reedy)
* track=1 rather than defaultbranch (Reedy)
* Update PHP_CodeSniffer to 3.0.2 (Kunal Mehta)

## 0.10.0 / 2017-07-01 ##
* Add sniff to prevent against using PHP 7's Unicode escape syntax (Kunal Mehta)
* Add sniff to verify type-casts use the short form (bool, int) (Kunal Mehta)
* Add sniff for `&$this` that causes warnings in PHP 7.1 (Kunal Mehta)
* Clean up DbrQueryUsageSniff (Umherirrender)
* Ensure all FunctionComment sniff codes are standard (Kunal Mehta)
* Exclude common folders (Umherirrender)
* Fix handling of nested parenthesis in ParenthesesAroundKeywordSniff (Kunal Mehta)
* IllegalSingleLineCommentSniff: Check return value of strrpos strictly (Kunal Mehta)
* Improve handling of multi-line class declarations (Kunal Mehta)
* Include sniff warning/error codes in test output (Kunal Mehta)
* Make DisallowEmptyLineFunctionsSniff apply to closures too (Kunal Mehta)
* Use correct notation for UTF-8 (Umherirrender)

## 0.9.0 / 2017-06-19 ##
* Add sniff to enforce "function (" for closures (Kunal Mehta)
* Add usage of && in generic_pass (addshore)
* Disallow `and` and `or` (Kunal Mehta)
* Don't require documentation for constructors without parameters (Kunal Mehta)
* Don't require documentation for '__toString' (Kunal Mehta)
* Don't require return/throws/param for doc blocks with @inheritDoc (Kunal Mehta)
* Expand list of standard methods that don't need documentation (Kunal Mehta)
* Fix FunctionComment.Missing sniff code (Kunal Mehta)
* Fix indentation (Umherirrender)
* Fix WhiteSpace/SpaceAfterClosureSniff (Antoine Musso)
* Make sure all files end with a newline (Kunal Mehta)
* test: ensure consistent report width (Antoine Musso)
* Update for CodeSniffer 3.0 (Kunal Mehta)
* Update squizlabs/PHP_CodeSniffer to 3.0.1 (Reedy)
* Use upstream CharacterBeforePHPOpeningTag sniff (Kunal Mehta)

## 0.8.0 / 2017-05-03 ##
* Add sniff for cast operator spacing (Sam Wilson)
* Allow filtering documentation requirements based on visibility (Kunal Mehta)
* Don't require documentation for test cases (Kunal Mehta)
* Don't require @return annotations for plain "return;" (Kunal Mehta)
* Explicitly check for method structure before using (Sam Wilson)
* Fix test result parsing, and correct new errors that were exposed (Sam Wilson)
* Prevent abstract functions being marked eligible (Sam Wilson)
* PHP_CodeSniffer to 2.9.0 (Paladox)

## 0.8.0-alpha.1 / 2016-09-21 ##
* Add detection for calling global functions in target classes. (Tao Xie)
* Add function commenting sniff. (Lethexie)
* Add .idea directory to .gitignore (Florian Schmidt)
* Add sniff to confirm function name using lower camel case. (Lethexie)
* Add test to verify SpaceBeforeClassBraceSniff handles extends (Kunal Mehta)
* Add the SpaceBeforeClassBraceSniff (Lethe)
* Add the SpaceBeforeControlStructureBraceSniff (Lethexie)
* Add usage to forbid superglobals like $_GET,$_POST (Lethe)
* Comments should start with new line. (Lethe)
* Disallow parenthesis around keywords like clone or require (Florian)
* Enable PSR2.Methods.FunctionClosingBrace sniff (Kunal Mehta)
* Fix reference parameters warning and no return function need return tag (Lethe)
* Fix single space expected on single line comment. (Lethexie)
* Make sure no empty line at the begin of the function. (Lethexie)
* Put failed examples and passed examples into a file. (Lethexie)
* Report warnings when $dbr->query() is used instead of $dbr->select(). (Tao Xie)
* Single Line comments no multiple '*'. (Lethe)
* Update squizlabs/php_codesniffer to 2.7.0 (Paladox)

## 0.7.2 / 2016-05-27 ##
* SpaceyParenthesisSniff: Don't remove last argument or array element (Kevin Israel)
* Expect specific output from sniffs (Erik Bernhardson)
* Assert fixers do as intended (Erik Bernhardson)

## 0.7.1 / 2016-05-06 ##
* Fix typo in IfElseStructureSniff (addshore)

## 0.7.0 / 2016-05-06 ##
* Also check for space after elseif in SpaceAfterControlStructureSniff (Lethexie)
* Factor our tokenIsNamespaced method (addshore)
* Make IfElseStructureSniff can detect and fix multiple white spaces after else (Lethexie)
* Make SpaceyParenthesisSniff can fix multiple white spaces between parentheses (Lethexie)
* Make spacey parenthesis sniff work with short array syntax (Kunal Mehta)
* Speed up PrefixedGlobalFunctionsSniff (addshore)
* Update squizlabs/php_codesniffer to 2.6.0 (Paladox)

## 0.6.0 / 2016-02-17 ##
* Add Generic.Arrays.DisallowLongArraySyntax to ruleset, autofix this repo (Kunal Mehta)
* Add sniff to detect consecutive empty lines in a file (Vivek Ghaisas)
* Disable Generic.Functions.CallTimePassByReference.NotAllowed (Kunal Mehta)
* Update squizlabs/php_codesniffer to 2.5.1 (Paladox)

## 0.5.1 / 2015-12-28 ##
* Avoid in_array for performance reasons (Thiemo Kreuz)
* build: Pass -s to phpcs for easier debugging (Kunal Mehta)
* Remove dead code from SpaceBeforeSingleLineCommentSniff (Thiemo Kreuz)
* Revert "CharacterBeforePHPOpeningTagSniff: Support T_HASHBANG for HHVM >=3.5,<3.7" (Legoktm)
* Simplify existing regular expressions (Thiemo Kreuz)
* build: Update phpunit to 4.8.18 (Paladox)
* Update squizlabs/php_codesniffer to 2.5.0 (Paladox)

## 0.5.0 / 2015-10-23 ##
* Add Generic.ControlStructures.InlineControlStructure to ruleset (Kunal Mehta)
* Add IfElseStructureSniff to handle else structures (TasneemLo)
* Handle multiple # comments in Space Before Comment (TasneemLo)
* Sniff to check assignment in while & if (TasneemLo)
* Sniff to warn when using `dirname(__FILE__)` (TasneemLo)

## 0.4.0 / 2015-09-26 ##
* Use upstream codesniffer 2.3.4 (Kunal Mehta & Paladox)
* Sniff to check for space in single line comments (Smriti.Singh)
* Automatically fix warnings caught by SpaceyParenthesisSniff (Kunal Mehta)
* Automatically fix warnings caught by SpaceAfterControlStructureSniff (Kunal Mehta)
* Add ignore list to PrefixedGlobalFunctionsSniff (Vivek Ghaisas)
* Add ignore list to ValidGlobalNameSniff (Vivek Ghaisas)
* Update jakub-onderka/php-parallel-lint to 0.9.* (Paladox)
* Automatically fix warnings caught by SpaceBeforeSingleLineCommentSniff (Kunal Mehta)

## 0.3.0 / 2015-06-19 ##
* Update README.md code formatting (Vivek Ghaisas)
* Don't require "wf" prefix on functions that are namespaced (Kunal Mehta)
* Simplify PHPUnit boostrap, require usage of composer for running tests (Kunal Mehta)
* SpaceyParenthesis: Check for space before opening parenthesis (Vivek Ghaisas)
* SpaceyParenthesesSniff: Search for extra/unnecessary space (Vivek Ghaisas)
* CharacterBeforePHPOpeningTagSniff: Support T_HASHBANG for HHVM >=3.5,<3.7 (Kunal Mehta)

## 0.2.0 / 2015-06-02 ##
* Fixed sniff that checks globals have a "wg" prefix (Divya)
* New sniff to detect unused global variables (Divya)
* New sniff to detect text before first opening php tag (Sumit Asthana)
* New sniff to detect alternative syntax such as "endif" (Vivek Ghaisas)
* New sniff to detect unprefixed global functions (Vivek Ghaisas)
* New sniff to detect "goto" usage (Harshit Harchani)
* Update ignore with some emacs files. (Mark A. Hershberger)
* Use upstream codesniffer 2.3.0 (Kunal Mehta)
* Make mediawiki/tools/codesniffer pass phpcs (Vivek Ghaisas)
* New sniff to check for spacey use of parentheses (Kunal Mehta)
* Modify generic pass test with a case of not-spacey parentheses (Vivek Ghaisas)
* Make failing tests fail only for specific respective reasons (Vivek Ghaisas)
* Change certain errors to warnings (Vivek Ghaisas)
* Update ExtraCharacters Sniff to allow shebang (Harshit Harchani)

## 0.1.0 / 2015-01-05 ##

* Initial tagged release
