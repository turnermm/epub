<?php
$ents = file($argv[1],FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$entities = array();
foreach($ents as $ent) {
  list($char,$num,$name) = preg_split('/\s+/',$ent);
  $entities[$name]=$num;
}
echo count($entities) . "\n";
$ents_2 = file($argv[2],FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach($ents_2 as $ent) {
  list($num,$name) = preg_split('/\s+/',$ent);
  $entities[$name]=$num;
  }
echo count($entities) . "\n";

$entities = serialize($entities);
file_put_contents('epub_ents.ser', $entities);

exit;

$epub_ents = unserialize(file_get_contents('epub_ents.ser'));
print_r($epub_ents);