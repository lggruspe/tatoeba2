<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TranscriptionsTable;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use App\Test\Fixture\TranscriptionsFixture;
use Cake\Utility\Hash;
use Cake\I18n\I18n;

class TranscriptionsTableTest extends TestCase {
    public $fixtures = array(
        'app.transcriptions',
        'app.sentences',
        'app.users'
    );

    public function setUp() {
        parent::setUp();
        $this->Transcription = TableRegistry::getTableLocator()->get('Transcriptions');
        $this->Fixtures = new TranscriptionsFixture();
        $this->AutoTranscr = $this->_installAutotranscriptionMock();
        $this->AutoTranscr
            ->expects($this->any())
            ->method('jpn_Jpan_to_Hrkt_generate')
            ->will($this->returnValue('autogenerated furigana'));
        $this->AutoTranscr
            ->expects($this->any())
            ->method('jpn_Jpan_to_Hrkt_validate')
            ->will($this->returnValue(true));
        $this->AutoTranscr
            ->expects($this->any())
            ->method('cmn_Hans_to_Hant_generate')
            ->will($this->returnValue('converted into traditional characters'));
        $this->AutoTranscr
            ->expects($this->any())
            ->method('cmn_Hans_to_Latn_generate')
            ->will($this->returnValue('autogenerated pinyin'));
        $this->AutoTranscr
            ->expects($this->any())
            ->method('yue_Hant_to_Latn_generate')
            ->will($this->returnValue('autogenerated jyutping'));
    }

    public function tearDown() {
        unset($this->Transcription);
        unset($this->Fixtures);
        parent::tearDown();
    }

    function _installAutotranscriptionMock() {
        $autotranscription = $this->getMockBuilder(Autotranscription::class)
			->setMethods([
                'jpn_Jpan_to_Hrkt_generate',
                'jpn_Jpan_to_Hrkt_validate',
                'cmn_Hans_to_Hant_generate',
                'cmn_Hans_to_Latn_generate',
                'yue_Hant_to_Latn_generate',
                'cmn_detectScript',
            ])
            ->getMock();

        $this->Transcription->setAutotranscription($autotranscription);
        return $autotranscription;
    }

    function _getRecord($record) {
        return $this->Fixtures->records[$record];
    }

    function _saveRecordWith($record, $changedFields) {
        $data = $this->_getRecord($record);
        $this->Transcription->deleteAll(array('1=1'));
        unset($data['id']);
        $data = array_merge($data, $changedFields);
        $transcription = $this->Transcription->newEntity($data);
        return (bool)$this->Transcription->save($transcription);
    }

    function _saveRecordWithout($record, $missingFields) {
        $data = $this->_getRecord($record);
        $this->Transcription->deleteAll(array('1=1'));
        unset($data['id']);
        foreach ($missingFields as $field) {
            unset($data[$field]);
        }
        $transcription = $this->Transcription->newEntity($data);
        return (bool)$this->Transcription->save($transcription);
    }

    function _assertValidRecordWith($record, $changedFields) {
        $this->assertTrue($this->_saveRecordWith($record, $changedFields));
    }
    function _assertValidRecordWithout($record, $changedFields) {
        $this->assertTrue($this->_saveRecordWithout($record, $changedFields));
    }
    function _assertInvalidRecordWith($record, $changedFields) {
        $this->assertFalse($this->_saveRecordWith($record, $changedFields));
    }
    function _assertInvalidRecordWithout($record, $missingFields) {
        $this->assertFalse($this->_saveRecordWithout($record, $missingFields));
    }

    function testValidateFirstRecord() {
        $this->_assertValidRecordWith(0, array());
    }

    function testScriptMustBeValid() {
        $this->_assertInvalidRecordWith(0, array('script' => 'ABCD'));
    }
    function testScriptRequired() {
        $this->_assertInvalidRecordWithout(0, array('script'));
    }
    function testScriptCantBeUpdated() {
        $transcription = $this->Transcription->get(1);
        $this->Transcription->delete($transcription); // to avoid uniqness error
        $data = $this->Transcription->newEntity([
            'id' => 2, 
            'script' => 'Hrkt'
        ]);

        $result = (bool)$this->Transcription->save($data);

        $this->assertFalse($result);
    }

    function testTextCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('text' => ''));
    }
    function testTextRequired() {
        $this->_assertInvalidRecordWithout(0, array('text'));
    }

    function testSentenceIdCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('sentence_id' => null));
    }
    function testSentenceIdRequired() {
        $this->_assertInvalidRecordWithout(0, array('sentence_id'));
    }
    function testSentenceIdCantBeUpdated() {
        $transcription = $this->Transcription->get(3);
        $this->Transcription->delete($transcription); // to avoid uniqness error
        $data = $this->Transcription->newEntity([
            'id' => 1, 'sentence_id' => 10
        ]);
        $result = (bool)$this->Transcription->save($data);

        $this->assertFalse($result);
    }

    function testCreatedCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('created' => ''));
    }
    function testCreatedIsAutomaticallySet() {
        $this->_assertValidRecordWithout(0, array('created'));
    }

    function testUserIdMustBeNumeric() {
        $this->_assertInvalidRecordWith(0, array('user_id' => 'melon'));
    }
    function testUserIdNotRequired() {
        $this->_assertValidRecordWithout(0, array('user_id'));
    }

    function testModifiedCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('modified' => ''));
    }
    function testModifiedIsAutomaticallySet() {
        $this->_assertValidRecordWithout(0, array('modified'));
    }

    function testTranscriptionMustBeUniqueForASentenceAndAScriptOnCreate() {
        $data = $this->_getRecord(0);
        unset($data['id']);
        $data = $this->Transcription->newEntity($data);
        $result = (bool)$this->Transcription->save($data);

        $this->assertFalse($result);
    }

    function testJapaneseCanBeTranscriptedToKanas() {
        $jpnSentence = $this->Transcription->Sentences->find()
            ->where(['lang' => 'jpn'])
            ->first()
            ->old_format;
        $result = $this->Transcription->transcriptableToWhat($jpnSentence);
        $this->assertTrue(isset($result['Hrkt']));
    }

    function testEditTrancriptionText() {
        $transcription = $this->Transcription->get(3);
        $this->Transcription->patchEntity($transcription, [
            'text' => 'we change this'
        ]);
        $result = (bool)$this->Transcription->save($transcription);
        $this->assertTrue($result);
    }
    function testEditTrancriptionTextCantBeEmpty() {
        $transcription = $this->Transcription->get(3);
        $this->Transcription->patchEntity($transcription, [
            'text' => ''
        ]);
        $result = (bool)$this->Transcription->save($transcription);
        $this->assertFalse($result);
    }

    function testCantSaveTranscriptionWithInvalidParent() {
        $nonexistantSentenceId = 52715278;
        $transcription = $this->Transcription->newEntity([
            'sentence_id' => $nonexistantSentenceId,
            'script' => 'Latn',
            'text' => 'Transcription with invalid parent.',
        ]);
        $result = $this->Transcription->save($transcription);
        $this->assertFalse($result);
    }

    function testCantSaveNotAllowedTranscriptionOnInsert() {
        $englishSentenceId = 1;
        $transcription = $this->Transcription->newEntity([
            'sentence_id' => $englishSentenceId,
            'script' => 'Latn',
            'text' => 'Transcript of English into Latin script??',
        ]);
        $result = $this->Transcription->save($transcription);
        $this->assertFalse($result);
    }

    function testCantSaveNotAllowedTranscriptionOnUpdate() {
        $transcription = $this->Transcription->newEntity([
            'id' => 1,
            'script' => 'Jpan',
            'text' => 'Transcript of Japanese into Japanese??',
        ]);
        $result = $this->Transcription->save($transcription);
        $this->assertFalse($result);
    }

    function testGenerateTranscriptionCallsGenerator() {
        $jpnSentence = $this->Transcription->Sentences->find()
            ->where(['lang' => 'jpn'])
            ->first();
        $this->AutoTranscr
            ->expects($this->once())
            ->method('jpn_Jpan_to_Hrkt_generate')
            ->with($jpnSentence->text, true);

        $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');
    }

    function testGenerateTranscriptionReturnsTranscription() {
        $jpnSentence = $this->Transcription->Sentences->get(6);

        $result = $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');
        $expected = array(
            'sentence_id' => 6,
            'script' => 'Hrkt',
            'text' => 'autogenerated furigana',
            'user_id' => null,
            'readonly' => false,
            'needsReview' => true,
            'type' => 'altscript',
            'id' => 'autogenerated',
        );
        $this->assertEquals($expected, $result);
    }

    function testGenerateTranscriptionReturnsTranscriptionWithParent() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentences->get(6);
        $expected = array(
            'id' => 'autogenerated',
            'sentence_id' => 6,
            'script' => 'Hrkt',
            'text' => 'autogenerated furigana',
            'user_id' => null,
            'readonly' => false,
            'type' => 'altscript',
            'needsReview' => true,
        );

        $result = $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');

        $this->assertEquals($expected, $result);
    }

    function testFindOnExistingRecordsReturnsReadonlyField() {
        $transcr = $this->Transcription->get(1);
        $result = array_key_exists('readonly', $transcr->old_format['Transcription']);
        $this->assertTrue($result);
    }

    function testGenerateTranscriptionCreatesGenerated() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentences->get(10);

        $this->Transcription->generateTranscription($jpnSentence, 'Hrkt', true);

        $created = $this->Transcription->find()->count();
        $this->assertEquals(1, $created);
    }

    function testGenerateTranscriptionCreatesProvidedTranscription() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentences->get(10);
        $data = array(
            'text' => 'あああ',
            'sentence_id' => 10,
            'script' => 'Hrkt',
            'user_id' => 33,
        );

        $created = $this->Transcription->generateTranscription($jpnSentence, 'Hrkt', true, $data);

        unset($created['modified']);
        unset($created['created']);
        $expected = array(
            'id' => 4,
            'sentence_id' => 10,
            'script' => 'Hrkt',
            'text' => 'あああ',
            'user_id' => 33,
            'readonly' => false,
            'needsReview' => false,
            'type' => 'altscript',
        );
        $this->assertEquals($expected, $created);
    }

    function testGenerateTranscriptionUpdatesProvidedTranscription() {
        $jpnSentence = $this->Transcription->Sentences->get(10);
        $data = array(
            'id' => '3',
            'text' => 'あああ',
            'sentence_id' => 10,
            'script' => 'Hrkt',
            'user_id' => 33,
        );

        $updated = $this->Transcription->generateTranscription($jpnSentence, 'Hrkt', true, $data);

        unset($updated['modified']);
        unset($updated['created']);
        $expected = array(
            'id' => 3,
            'sentence_id' => 10,
            'script' => 'Hrkt',
            'text' => 'あああ',
            'user_id' => 33,
            'readonly' => false,
            'needsReview' => false,
            'type' => 'altscript',
        );
        $this->assertEquals($expected, $updated);
    }

    function testGenerateTranscriptionUpdates() {
        $transcr = $this->Transcription->get(1);
        $jpnSentence = $this->Transcription->Sentences->get(
            $transcr->sentence_id
        );
        $transcr->text = 'あああ';

        $this->Transcription->generateTranscription(
            $jpnSentence, 'Hrkt', true, $transcr->old_format['Transcription']
        );

        $updated = $this->Transcription->find('all')
            ->where(['sentence_id' => $transcr->sentence_id])
            ->toList();
        $this->assertEquals('あああ', $updated[0]->text);
    }

    function testGenerateAndSaveAllTranscriptionsForJapanese() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentences->get(6);

        $this->Transcription->generateAndSaveAllTranscriptionsFor($jpnSentence);

        $created = $this->Transcription->find()
            ->where(['sentence_id' => 6])
            ->count();
        $this->assertEquals(1, $created);
    }

    function testGenerateAndSaveAllTranscriptionsReturnValue() {
        $this->Transcription->deleteAll('1=1');
        $cmnSentence = $this->Transcription->Sentences->get(2);

        $returned = $this->Transcription->generateAndSaveAllTranscriptionsFor($cmnSentence);

        $created = $this->Transcription->find()
            ->where(['sentence_id' => 2])
            ->count();
        $this->assertEquals($created, $returned);
    }

    function testGenerateAndSaveAllTranscriptionsForChinese() {
        $this->Transcription->deleteAll('1=1');
        $cmnSentence = $this->Transcription->Sentences->get(2);

        $this->Transcription->generateAndSaveAllTranscriptionsFor($cmnSentence);

        $created = $this->Transcription->find()
            ->where(['sentence_id' => 2])
            ->count();
        $this->assertEquals(2, $created);
    }

    function testGenerateAndSaveTranscriptionsForCantonese() {
        $this->Transcription->deleteAll('1=1');
	    $yueSentenceId = 11;
        $yueSentence = $this->Transcription->Sentences->get($yueSentenceId);

        $this->Transcription->generateTranscription($yueSentence, 'Latn', true);

        $created = $this->Transcription->find()
            ->where(['sentence_id' => $yueSentenceId])
            ->count();
        $this->assertEquals(1, $created);
    }

    function testCanCreateReadonlyTranscriptions() {
        $this->_assertValidRecordWith(1, array());
    }

    function testCannotUpdateReadonlyTranscriptions() {
        $result = (bool)$this->Transcription->saveTranscription(array(
            'id' => 2,
            'sentence_id' => 2,
            'script' => 'Hant',
            'text' => '問題的根源是，在當今世界，愚人充滿了自信，而智者充滿了懷疑。',
        ));
        $this->assertFalse($result);
    }

    function testSaveTranscriptionChecksUserProvidedTranscriptionValidityOnCreate() {
        $transcr = $this->Transcription->find()
            ->where(['sentence_id' => 10])
            ->first();
        $this->Transcription->delete($transcr);

        $transcr = $transcr->old_format;
        unset($transcr['Transcription']['id']);
        $transcr['Transcription']['user_id'] = 4;

        $this->AutoTranscr = $this->_installAutotranscriptionMock();
        $this->AutoTranscr
            ->expects($this->any())
            ->method('jpn_Jpan_to_Hrkt_validate')
            ->will($this->returnValue(false));

        $result = (bool)$this->Transcription->saveTranscription($transcr['Transcription']);
        $this->assertFalse($result);
    }

    function testSaveTranscriptionDontCheckGeneratedTranscriptionValidityOnCreate() {
        $transcr = $this->Transcription->find()
            ->where(['sentence_id' => 10])
            ->first();
        $this->Transcription->delete($transcr);

        $transcr = $transcr->old_format;
        unset($transcr['Transcription']['id']);

        $this->AutoTranscr = $this->_installAutotranscriptionMock();
        $this->AutoTranscr
            ->expects($this->any())
            ->method('jpn_Jpan_to_Hrkt_validate')
            ->will($this->returnValue(false));

        $result = (bool)$this->Transcription->saveTranscription($transcr['Transcription']);
        $this->assertTrue($result);
    }

    function testSaveTranscriptionChecksUserProvidedTranscriptionValidityOnUpdate() {
        $transcr = $this->Transcription->find()
            ->where(['sentence_id' => 10])
            ->first()->old_format;
        $transcr['Transcription']['text'] = 'something new';
        $transcr['Transcription']['user_id'] = 4;

        $this->AutoTranscr = $this->_installAutotranscriptionMock();
        $this->AutoTranscr
            ->expects($this->any())
            ->method('jpn_Jpan_to_Hrkt_validate')
            ->will($this->returnValue(false));

        $result = (bool)$this->Transcription->saveTranscription($transcr['Transcription']);
        $this->assertFalse($result);
    }

    function testSaveTranscriptionDontCheckGeneratedTranscriptionValidityOnUpdate() {
        $transcr = $this->Transcription->find()
            ->where(['sentence_id' => 10])
            ->first()->old_format;
        $transcr['Transcription']['text'] = 'something new';

        $this->AutoTranscr = $this->_installAutotranscriptionMock();
        $this->AutoTranscr
            ->expects($this->any())
            ->method('jpn_Jpan_to_Hrkt_validate')
            ->will($this->returnValue(false));

        $result = (bool)$this->Transcription->saveTranscription($transcr['Transcription']);
        $this->assertTrue($result);
    }

    function testSaveTranscriptionSetsNeedsReviewToFalseWhenSavedByAUser() {
        $transcr = $this->Transcription->find()
            ->where(['sentence_id' => 10])
            ->first()->old_format;
        $transcr['Transcription']['text'] = 'something new';
        $transcr['Transcription']['user_id'] = 4;

        $this->Transcription->saveTranscription($transcr['Transcription']);

        $transcr = $this->Transcription->find()
            ->where(['sentence_id' => 10])
            ->first()->old_format;
        $this->assertFalse($transcr['Transcription']['needsReview']);
    }

    function testAddGeneratedTranscriptionsAddsEverything() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentences->get(10);

        $result = $this->Transcription->addGeneratedTranscriptions(
            array(),
            $jpnSentence
        );

        $this->assertEquals(1, count($result));
        $this->assertEquals('Hrkt', $result[0]['script']);
    }

    function testAddGeneratedTranscriptionsDontDoubleGenerate() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentences->get(10);

        $this->Transcription = $this->getMockBuilder(TranscriptionsTable::class)
            ->setMethods(['generateTranscription'])
            ->getMock();
        $this->Transcription
            ->expects($this->once())
            ->method('generateTranscription')
            ->will($this->returnValue(array()));

        $this->Transcription->addGeneratedTranscriptions(
            array(),
            $jpnSentence
        );
    }

    function testAddGeneratedTranscriptionsAddsNothing() {
        $jpnSentence = $this->Transcription->Sentences->get(6);
        $existingTranscriptions = $this->Transcription->find()
            ->where(['sentence_id' => 6])
            ->toList();

        $result = $this->Transcription->addGeneratedTranscriptions(
            $existingTranscriptions,
            $jpnSentence
        );

        $this->assertEquals(1, count($result));
        $this->assertEquals('Hrkt', $result[0]['script']);
    }

    function testAddGeneratedTranscriptionsKeepsOrder() {
        $cmnSentenceId = 2;
        $cmnSentence = $this->Transcription->Sentences->get($cmnSentenceId);
        $transcription = [
            'sentence_id' => $cmnSentenceId,
            'script' => 'Latn',
            'text' => 'blah blah blah in pinyin',
        ];
        $result = $this->Transcription->saveTranscription($transcription);

        $result = $this->Transcription->addGeneratedTranscriptions(
            array($result),
            $cmnSentence
        );

        $this->assertEquals('Hant', $result[0]['script']);
        $this->assertEquals('Latn', $result[1]['script']);
    }

    function testDetectScriptCallsDetector() {
        $cmnSentence = $this->Transcription->Sentences->find()
            ->where(['lang' => 'cmn'])
            ->first();
        $this->AutoTranscr
            ->expects($this->once())
            ->method('cmn_detectScript')
            ->with($cmnSentence->text);

        $this->Transcription->detectScript(
            $cmnSentence->lang,
            $cmnSentence->text
        );
    }

    function testSave_correctDateUsingArabicLocale() {
        I18n::setLocale('ar');
        $data = $this->_getRecord(0);
        $this->Transcription->deleteAll('true');
        unset($data['id'], $data['created'], $data['modified']);
        $transcription = $this->Transcription->newEntity($data);
        $added = $this->Transcription->save($transcription);
        $returned = $this->Transcription->get($added->id);
        $this->assertEquals($added->created, $returned->created);
        $this->assertEquals($added->modified, $returned->modified);
    }
}
