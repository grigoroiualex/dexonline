<?php

require_once("../../phplib/Core.php");
User::mustHave(User::PRIV_WOTD | User::PRIV_EDIT);
Util::assertNotMirror();

$query = Request::get('term');
$definitions = Model::factory('Definition')
             ->where('status', Definition::ST_ACTIVE)
             ->where_like('lexicon', "{$query}%")
             ->order_by_asc('lexicon')
             ->limit(20)
             ->find_many();

$resp = ['results' => []];
foreach ($definitions as $definition){
  $source = Source::get_by_id($definition->sourceId);
  $resp['results'][] = [
    'id' => $definition->id,
    'lexicon' => $definition->lexicon,
    'text' => mb_substr($definition->internalRep, 0, 80),
    'source' => $source->shortName,
  ];
}

header('Content-Type: application/json');
echo json_encode($resp);
