<?php
require_once("../../phplib/Core.php");
User::mustHave(User::PRIV_EDIT);
Util::assertNotMirror();

$definitionId = Request::get('definitionId');
$userId = User::getActiveId();

if (!$definitionId) {
  // User requested an OCR definition. Try to find one.
  $ocr = OCR::getNext($userId);
  if (!$ocr) {
    FlashMessage::add('Lista cu definiții OCR este goală.', 'warning');
    Util::redirect('index.php');
  }

  // Found one, create the Definition and update the OCR.
  $d = Model::factory('Definition')->create();
  $d->status = Definition::ST_ACTIVE;
  $d->userId = $userId;
  $d->sourceId = $ocr->sourceId;
  $d->similarSource = 0;
  $d->structured = 0;
  $d->internalRep = $ocr->ocrText;
  $d->process();
  $d->save();

  $ocr->definitionId = $d->id;
  $ocr->editorId = $userId;
  $ocr->status = 'published';
  $ocr->save();

  Log::notice("Imported definition {$d->id} ({$d->lexicon}) from OCR {$ocr->id}");

  // Redirect to the new Definition.
  Util::redirect("definitionEdit.php?definitionId={$d->id}&isOCR=1");
}

if (!($d = Definition::get_by_id($definitionId))) {
  FlashMessage::add("Nu există nicio definiție cu ID-ul {$definitionId}.");
  Util::redirect("index.php");
}

$orig = Definition::get_by_id($definitionId); // for comparison

// Load request fields and buttons.
$isOCR = Request::get('isOCR');
$entryIds = Request::getArray('entryIds');
$sourceId = Request::get('source');
$similarSource = Request::has('similarSource');
$structured = Request::has('structured');
$internalRep = Request::get('internalRep');
$status = Request::get('status', null);
$tagIds = Request::getArray('tagIds');

$saveButton = Request::has('saveButton');
$nextOcrBut = Request::has('but_next_ocr');

if ($saveButton || $nextOcrBut) {
  $d->internalRep = $internalRep;
  $d->status = (int)$status;
  $d->sourceId = (int)$sourceId;
  $d->similarSource = $similarSource;
  $d->structured = $structured;

  $footnotes = $d->process();

  if (!FlashMessage::hasErrors()) {
    // Save the new entries, load the rest.
    $noAccentNag = false;
    $entries = [];
    foreach ($entryIds as $entryId) {
      if (Str::startsWith($entryId, '@')) {
        // create a new lexeme and entry
        $form = substr($entryId, 1);
        $l = Lexeme::create($form, 'T', '1');
        $e = Entry::createAndSave($l, true);
        $l->save();
        $l->regenerateParadigm();
        EntryLexeme::associate($e->id, $l->id);

        if (strpos($form, "'") === false) {
          $noAccentNag = true;
        }

      } else {
        $e = Entry::get_by_id($entryId);
      }
      $entries[] = $e;
    }
    if ($noAccentNag) {
      FlashMessage::add('Vă rugăm să indicați accentul pentru lexemele noi oricând se poate.',
                        'warning');
    }

    // Save the definition and delete the typos associated with it.
    $d->save();
    Footnote::delete_all_by_definitionId($d->id);
    foreach ($footnotes as $f) {
      $f->definitionId = $d->id;
      $f->save();
    }
    Typo::delete_all_by_definitionId($d->id);

    if ($d->structured && ($d->internalRep != $orig->internalRep)) {
      FlashMessage::add('Ați modificat o definiție deja structurată. Dacă se poate, ' .
                        'vă rugăm să modificați corespunzător și arborele de sensuri.',
                        'warning');
    }
    if (!$d->lexicon) {
      FlashMessage::add('Câmpul lexicon este vid. Aceasta se întâmplă de obicei când omiteți ' .
                        'să încadrați cuvântul-titlu între @...@.',
                        'warning');
    }

    if ($d->status == Definition::ST_DELETED) {
      EntryDefinition::dissociateDefinition($d->id);
    } else {
      EntryDefinition::update(Util::objectProperty($entries, 'id'), $d->id);
    }

    ObjectTag::wipeAndRecreate($d->id, ObjectTag::TYPE_DEFINITION, $tagIds);

    Log::notice("Saved definition {$d->id} ({$d->lexicon})");

    if ($nextOcrBut) {
      // cause the next OCR definition to load
      Util::redirect('definitionEdit.php');
    } else {
      $url = "definitionEdit.php?definitionId={$d->id}";
      if ($isOCR) {
        // carry this around so the user can click "Save" any number of times, then "next OCR".
        $url .= "&isOCR=1";
      }
      Util::redirect($url);
    }
  } else {
    // There were errors saving.
  }
} else {
  // First time loading this page -- not a save.
  RecentLink::add(sprintf('Definiție: %s (%s) (ID=%s)',
                          $d->lexicon, $d->getSource()->shortName, $d->id));

  $entries = $d->getEntries();
  $entryIds = Util::objectProperty($entries, 'id');

  $dts = ObjectTag::getDefinitionTags($d->id);
  $tagIds = Util::objectProperty($dts, 'tagId');
}

$typos = Model::factory('Typo')
  ->where('definitionId', $d->id)
  ->order_by_asc('id')
  ->find_many();

if ($isOCR && empty($entryIds)) {
  $d->extractLexicon();
  if ($d->lexicon) {
      $entries = Model::factory('Definition')
          ->table_alias('d')
          ->distinct('e.entryId')
          ->join('EntryDefinition', ['d.id', '=', 'e.definitionId'], 'e')
          ->where('d.lexicon', $d->lexicon)
          ->find_many();
      $entryIds = array_unique(Util::objectProperty($entries, 'entryId'));
  }
}

// If we got here, either there were errors saving, or this is the first time
// loading the page.

// create a stub SearchResult so we can show the menu
$row = new SearchResult();
$row->definition = $d;
$row->source = $d->getSource();

SmartyWrap::assign('isOCR', $isOCR);
SmartyWrap::assign('def', $d);
SmartyWrap::assign('row', $row);
SmartyWrap::assign('source', $d->getSource());
SmartyWrap::assign('sim', SimilarRecord::create($d, $entryIds));
SmartyWrap::assign('user', User::get_by_id($d->userId));
SmartyWrap::assign('entryIds', $entryIds);
SmartyWrap::assign('tagIds', $tagIds);
SmartyWrap::assign('typos', $typos);
SmartyWrap::assign("allModeratorSources", Model::factory('Source')->where('canModerate', true)->order_by_asc('displayOrder')->find_many());
SmartyWrap::addCss('tinymce', 'admin', 'diff');
SmartyWrap::addJs('select2Dev', 'tinymce', 'cookie', 'frequentObjects');
SmartyWrap::display('admin/definitionEdit.tpl');
