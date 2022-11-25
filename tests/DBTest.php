<?php /** @noinspection SqlDialectInspection */

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace Tests\Unit;

use Codeception\Test\Unit;
use Socodo\ORM\DB;
use Tests\Support\UnitTester;

class DBTest extends Unit
{
    /** @var UnitTester Unit tester. */
    protected UnitTester $tester;

    /**
     * Before event.
     *
     * @return void
     */
    protected function _before (): void
    {
        new DB('127.0.0.1:3306', 'socodo', 'socodo', 'socodo');
    }

    /**
     * DB::getInstance()
     *
     * @return void
     */
    public function testGetInstance (): void
    {
        $db = new DB('127.0.0.1:3306', 'socodo', 'socodo', 'socodo');
        $this->assertSame($db, DB::getInstance());
    }

    /**
     * DB::query()
     *
     * @return void
     */
    public function testQuery (): void
    {
        $db = DB::getInstance();

        $stmt = $db->query('SELECT * FROM user');
        $this->assertNotFalse($stmt);

        $stmt = $db->query('SELECT * FROM user WHERE 1 = 1');
        $this->assertNotFalse($stmt);

        $stmt = $db->query('SELECT * FROM user WHERE id = :id', [ ':id' => 1 ]);
        $this->assertNotFalse($stmt);
    }

    /**
     * DB::rawQuery()
     *
     * @return void
     */
    public function testRawQuery (): void
    {
        $db = DB::getInstance();

        $stmt = $db->rawQuery('SELECT * FROM user WHERE id = 1');
        $this->assertNotFalse($stmt);
    }

    /**
     * DB::fetch()
     *
     * @return void
     */
    public function testFetch (): void
    {
        $db = DB::getInstance();

        $stmt = $db->query('SELECT * FROM user');
        while ($result = $db->fetch($stmt))
        {
            $this->assertIsObject($result);
            $this->tester->seeInDatabase('user', [ 'id' => $result->id ]);
        }

        $stmt = $db->query('SELECT * FROM user WHERE id = :id', [ ':id' => 1 ]);
        $result = $db->fetch($stmt);
        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->tester->seeInDatabase('user', [ 'id' => $result->id ]);
        $this->assertFalse($db->fetch($stmt));

        $stmt = $db->query('INSERT INTO user (email_address, profile_id) VALUES (:emailAddress, null)', [ ':emailAddress' => 'fetch@fetch.com' ]);
        $result = $db->fetch($stmt);
        $this->assertFalse($result);
    }

    /**
     * DB::fetchAll()
     *
     * @return void
     */
    public function testFetchAll (): void
    {
        $db = DB::getInstance();

        $stmt = $db->query('SELECT * FROM user');
        $result = $db->fetchAll($stmt);
        $this->assertIsArray($result);
        foreach ($result as $item)
        {
            $this->assertIsObject($item);
            $this->tester->seeInDatabase('user', [ 'id' => $item->id ]);
        }
    }

    /**
     * DB::queryThenFetch()
     *
     * @return void
     */
    public function testQueryThenFetch (): void
    {
        $db = DB::getInstance();

        $result = $db->queryThenFetch('SELECT * FROM user WHERE id = :id', [ ':id' => 1 ]);
        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->tester->seeInDatabase('user', [ 'id' => $result->id ]);
    }

    /**
     * DB::getLastInsertId()
     *
     * @return void
     */
    public function testGetLastInsertId (): void
    {
        $db = DB::getInstance();

        $db->rawQuery('INSERT INTO user (email_address, profile_id) VALUES ("last@id.com", null)');
        $this->assertIsNumeric($db->getLastInsertId());

        $db->rawQuery('SELECT * FROM user');
        $this->assertEquals(0, $db->getLastInsertId());

        $stmt = $db->rawQuery('SELECT * FROM user WHERE id = 1');
        $this->assertEquals(0, $db->getLastInsertId());
        $db->fetch($stmt);
        $this->assertEquals(0, $db->getLastInsertId());
    }

    /**
     * DB::begin(), DB::commit(), DB::rollback()
     *
     * @return void
     */
    public function testTransaction (): void
    {
        $db = DB::getInstance();

        $q = 'INSERT INTO user (email_address, profile_id) VALUES ("rollback@test.com", null)';

        $db->begin();
        $db->rawQuery($q);
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'rollback@test.com' ]);
        $db->rollback();
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'rollback@test.com' ]);

        $q = 'INSERT INTO user (email_address, profile_id) VALUES ("commit@test.com", null)';

        $db->begin();
        $db->rawQuery($q);
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'commit@test.com' ]);
        $db->commit();
        $this->tester->seeInDatabase('user', [ 'email_address' => 'commit@test.com' ]);

        $q = 'INSERT INTO user (email_address, profile_id) VALUES ("nested0@test.com", null)';
        $db->begin();
        $db->rawQuery($q);
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'nested0@test.com' ]);

        $q = 'INSERT INTO user (email_address, profile_id) VALUES ("nested1@test.com", null)';
        $db->begin();
        $db->rawQuery($q);
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'nested1@test.com' ]);
        $db->rollback();
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'nested1@test.com' ]);

        $db->begin();
        $db->rawQuery($q);
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'nested1@test.com' ]);
        $db->commit();
        $this->tester->dontSeeInDatabase('user', [ 'email_address' => 'nested1@test.com' ]);

        $db->commit();
        $this->tester->seeInDatabase('user', [ 'email_address' => 'nested0@test.com' ]);
        $this->tester->seeInDatabase('user', [ 'email_address' => 'nested1@test.com' ]);
    }
}
