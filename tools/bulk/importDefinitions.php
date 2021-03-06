<?php
require_once(__DIR__ . '/../../phplib/Core.php');

$shortopts = "f:u:s:t:x:p:hvidbcC";
$options = getopt($shortopts);
$vverbose = true;
$vvverbose = true;

function HELP() {
  exit("
Usage: From tools/ directory run:
    php bulk/importDefinitions.php options

Options:
    -f fileName (mandatory)
    -u userId (mandatory)
    -s sourceId (mandatory)
    -c check against sourceId
    -C just split the input file the new, existing and to be checked files (using sourceId)
    -t status (mandatory: 0 - active, 1 - temporary, 2 - deleted, 3 - hidden)
    -i = use inflections for multilexeme entries
    -x = exclude lexemes beginning with
    -p = split lexeme using this char
    -b = verbs are defined using the particle 'a ' at the beginning
    -d = dry run
    -h = help
    -v = verbose

Example:
    php bulk/importDefinitions.php -f definitions.txt -u 471 -s 19 -t 0 -d -v > result.log

");
}

function CHECK($option, $c) {
  if (!is_array($option))
    exit("Error reading options\n");

  if (!isset($option[$c])) {
    echo "Missing mandatory option $c\n";
    HELP();
  }
}

function remove_verbs_particle($verb) {
  foreach(['a se ', 'a (se) ', 'a '] as $part) {
    if (strpos($verb, $part) === 0) {
      return substr($verb, strlen($part));
    }
  }
}

function canonic_string($string) {
  return str_replace([" ", "\n", "!", "*", ",", ".", "@", "$"], "", $string);
  // str_replace(["á","é","í","ó","ú"], ["'a","'e","'i","'o","'u"], $tstring);
}

/*** check options ***/

if(isset($options['h'])) HELP();

CHECK($options, 'u');
$userId = $options['u'];

CHECK($options, 's');
$sourceId = $options['s'];

CHECK($options, 'f');
$fileName = $options['f'];
if (!file_exists($fileName)) exit("Error: file $fileName doesn't exist!\n");

CHECK($options, 't');
$status = $options['t'];

$checkSourceId = false;
if (isset($options['c'])) {
  $checkSourceId = true;
}

$checkingDryrun = false;
if (isset($options['C'])) {
  $checkingDryrun = true;
}

$excludeChar = NULL;
if (isset($options['x'])) {
  $excludeChar = $options['x'];
}

$splitChar = NULL;
if (isset($options['p'])) {
  $splitChar = $options[''];
}

$verbs_part = false;
if (isset($options['b'])) {
  $verbs_part = true;
}

$verbose = false;
if (isset($options['v'])) {
  $verbose = true;
}

$allowInflected = false;
if (isset($options['i'])) {
  $allowInflected = true;
}

$dryrun = false;
if (isset($options['d'])) {
  $dryrun = true;
}

/*** main loop ***/
if($verbose) echo("Everything OK, start processing...\n");

$lines = file($fileName);
if($verbose) echo("File read. Start inserting the definitions...\n");

if ($checkingDryrun) {
  $new = fopen($fileName . "-NEW", 'w');
  $existing = fopen($fileName . "-EXISTING", 'w');
  $tobechecked = fopen($fileName . "-TOBECHECKED", 'w');
}

$i = 0;
while ($i < count($lines)) {
  $def = $lines[$i++];
  preg_match('/^@([^@]+)@/', $def, $matches);
  if (!is_array($matches) || !array_key_exists(1, $matches)) {
      if ($verbose) echo "ERROR: " + count($matches);
      if ($verbose) print_r($matches);
      continue;
  }
  $lname = $matches[1];
  if($verbs_part && strpos($lname, 'a ')===0) {
    $lname = remove_verbs_particle($lname);
  }
  $lname = preg_replace("/[!*'^1234567890]/", "", $lname);

  if ($checkSourceId) {
    if($verbose) echo(" * Check if the definition for '{$lname}' already exists\n");
    $defDict = Model::factory('Definition')->where('lexicon', $lname)->where('sourceId', $sourceId)->where('status', 0)->find_many();

    if ( count($defDict) ) {
      if($verbose) echo("\t Definition for '{$lname}' exists in the checked dictionary\n");
      if($vverbose) echo("IMPORTĂM DEF: {$def}\n");
      $isMatch = false;
      $tdef = canonic_string($def);
      foreach ($defDict as $dd) {
        if($vverbose) echo("ÎN DEXONLINE: {$dd->internalRep}\n");
        $tdd = canonic_string($dd->internalRep);
        if ($tdef == $tdd){
          $isMatch = true;
          $mdef = $tdd;
          if ($vverbose) echo "MATCH!!!\n";
          continue;
        }
        else {
            if($vvverbose) echo("\n");
            if($vvverbose) echo("ÎN DEXONLINE: {$tdd}\n");
            if($vvverbose) echo("DEF IMPORTAT: {$tdef}\n");
            if($vvverbose) echo("\n");
        }
      }
      if ($isMatch) {
        if ($checkingDryrun) fwrite($existing, $def);
        continue;
      }
      else {
        if($verbose) echo("\t A definition for the '{$lname}' was found, but it is different – we'll import the new one!\n");
        if ($checkingDryrun) fwrite($tobechecked, $def);
      }
    }
    else {
      if($verbose) echo("\t Definition not exist – it will be added!\n");
      if ($checkingDryrun) fwrite($new, $def);
    }
  }

  if ($checkSourceId && $checkingDryrun) {
      continue;
  }

  if($verbose) echo(" * Inserting definition for '$lname'...\n");
  $definition = Model::factory('Definition')->create();
  $definition->userId = $userId;
  $definition->sourceId = $sourceId;
  $definition->status = $status;
  $definition->internalRep = $def;
  $definition->process(false);
  $definition->save();
  if($verbose) echo("\tAdded definition {$definition->id} ({$definition->lexicon})\n");

  $lname = addslashes($lname);
  $names = preg_split("/[-\s,\/()]+/", $lname);
  foreach ($names as $name) {
    if ($name == '') continue;
    if (isset($excludeChar) && ($name[0])==$excludeChar) continue;
    $name = str_replace("'", '', $name);
    $name = str_replace("\\", '', $name);

    if($verbose) echo("\t * Process part: '{$name}'\n");

    $lexemes = Lexeme::get_all_by_form($name);
    if (!count($lexemes)) {
      $lexemes = Lexeme::get_all_by_formNoAccent($name);
    }

    if ($allowInflected) {
      if (!count($lexemes)) {
        $lexemes = Model::factory('Lexeme')
          ->table_alias('l')
          ->select('l.*')
          ->join('InflectedForm', 'l.id = i.lexemeId', 'i')
          ->where('i.formNoAccent', $name)
          ->find_many();
        if ( count($lexemes) ) {
          if($verbose) echo("\t\tFound inflected form {$name} for lexeme {$lexemes[0]->id} ({$lexemes[0]->form})\n");
        }
      }
    }

    // procedura de refolosire a lexemului sau de regenerare
    if (count($lexemes)) {
      // Reuse existing lexeme.
      $lexeme = $lexemes[0];
      $entry = $lexeme->getEntries()[0];
      if($verbose) echo("\t\tReusing lexeme {$lexeme->id} ({$lexeme->form})\n");
    } else {
      if($verbose) echo("\t\tCreating a new lexeme for name {$name}\n");
      if (!$dryrun) {
        // Create a new lexeme.
        $lexeme = Lexeme::create($name, 'T', '1');
        $entry = Entry::createAndSave($lexeme->formNoAccent);
        $lexeme->deepSave();
        if($verbose) echo("\t\tCreated lexeme {$lexeme->id} ({$lexeme->form})\n");
      }
    }

    // procedura de asociere a definiției cu lexemul de mai sus
    if($verbose) echo("\t\tAssociate entry {$entry->id} ({$entry->description}) " .
                      "for lexeme {$lexeme->id} ({$lexeme}) " .
                      "to definition ({$definition->id})\n");
    if (!$dryrun) {
      EntryDefinition::associate($entry->id, $definition->id);
    }
  }
}

if ($checkingDryrun) {
  fclose($new);
  fclose($existing);
  fclose($tobechecked);
}
