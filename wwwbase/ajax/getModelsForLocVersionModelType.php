<?php
require_once("../../phplib/Core.php");

$modelType = Request::get('modelType');
$locVersion = Request::get('locVersion');

if ($locVersion) {
  LocVersion::changeDatabase($locVersion);
}

$models = FlexModel::loadByType($modelType);

$resp = [];
foreach ($models as $m) {
  $resp[] = ['id' => $m->id, 'number' => $m->number, 'exponent' => $m->exponent];
}
print json_encode($resp);
