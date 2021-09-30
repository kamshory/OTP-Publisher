<?php

namespace Composer;

use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'No version set (parsed as 1.0.0)',
    'version' => '1.0.0.0',
    'aliases' => 
    array (
    ),
    'reference' => NULL,
    'name' => '__root__',
  ),
  'versions' => 
  array (
    '__root__' => 
    array (
      'pretty_version' => 'No version set (parsed as 1.0.0)',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'cboden/ratchet' => 
    array (
      'pretty_version' => 'v0.2.8',
      'version' => '0.2.8.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ebd17c6675b51044e711a1089b1534fd8c68c9e0',
    ),
    'evenement/evenement' => 
    array (
      'pretty_version' => 'v1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fa966683e7df3e5dd5929d984a44abfbd6bafe8d',
    ),
    'guzzle/common' => 
    array (
      'pretty_version' => 'v3.7.4',
      'version' => '3.7.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '5126e268446c7e7df961b89128d71878e0652432',
    ),
    'guzzle/http' => 
    array (
      'pretty_version' => 'v3.7.4',
      'version' => '3.7.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '3420035adcf312d62a2e64f3e6b3e3e590121786',
    ),
    'guzzle/parser' => 
    array (
      'pretty_version' => 'v3.7.4',
      'version' => '3.7.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a25c2ddda1c52fb69a4ee56eb530b13ddd9573c2',
    ),
    'guzzle/stream' => 
    array (
      'pretty_version' => 'v3.7.4',
      'version' => '3.7.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a86111d9ac7db31d65a053c825869409fe8fc83f',
    ),
    'ircmaxell/password-compat' => 
    array (
      'pretty_version' => 'v1.0.4',
      'version' => '1.0.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '5c5cde8822a69545767f7c7f3058cb15ff84614c',
    ),
    'react/event-loop' => 
    array (
      'pretty_version' => 'v0.3.5',
      'version' => '0.3.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '13e03b17e54ea864c6653a2cf6d146dad8464e91',
    ),
    'react/socket' => 
    array (
      'pretty_version' => 'v0.3.4',
      'version' => '0.3.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '19bc0c4309243717396022ffb2e59be1cc784327',
    ),
    'react/stream' => 
    array (
      'pretty_version' => 'v0.3.4',
      'version' => '0.3.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'feef56628afe3fa861f0da5f92c909e029efceac',
    ),
    'symfony/event-dispatcher' => 
    array (
      'pretty_version' => 'v2.8.52',
      'version' => '2.8.52.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a77e974a5fecb4398833b0709210e3d5e334ffb0',
    ),
    'symfony/http-foundation' => 
    array (
      'pretty_version' => 'v2.8.52',
      'version' => '2.8.52.0',
      'aliases' => 
      array (
      ),
      'reference' => '3929d9fe8148d17819ad0178c748b8d339420709',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.17.0',
      'version' => '1.17.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fa79b11539418b02fc5e1897267673ba2c19419c',
    ),
    'symfony/polyfill-php54' => 
    array (
      'pretty_version' => 'v1.17.0',
      'version' => '1.17.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '3c71ff0f90fcbd00ca8966f35526e3cbad15d31d',
    ),
    'symfony/polyfill-php55' => 
    array (
      'pretty_version' => 'v1.17.0',
      'version' => '1.17.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '875267200645e116261c31ff20c641dbae90fd8d',
    ),
  ),
);







public static function getInstalledPackages()
{
return array_keys(self::$installed['versions']);
}









public static function isInstalled($packageName)
{
return isset(self::$installed['versions'][$packageName]);
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

$ranges = array();
if (isset(self::$installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = self::$installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}





public static function getVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['version'])) {
return null;
}

return self::$installed['versions'][$packageName]['version'];
}





public static function getPrettyVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return self::$installed['versions'][$packageName]['pretty_version'];
}





public static function getReference($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['reference'])) {
return null;
}

return self::$installed['versions'][$packageName]['reference'];
}





public static function getRootPackage()
{
return self::$installed['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
}
}
