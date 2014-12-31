# Changelog #

## v4.2.0 (?) ##

  * ClassLoader::findFile now does better job of canonizing directory separators
  * ClassLoader::loadFile will now always return true or thrown an exception,
    irregardless of verbose setting
  * Class loader will now look in include_path last, for real
  * Some documentation clarification and fixes
  * Overall changes in code to reduce the complexity

## v4.1.0 (2014-06-19) ##

  * add*Path methods accept a mixed list of paths with namespaces and paths
    without namespace
  * Added getBasePaths() and getPrefixPaths() methods to retrieve added paths
  * Clarified documentation on possibility of using arrays for multiple paths

## v4.0.1 (2014-05-28) ##

  * Code cleanup and documentation fixes
