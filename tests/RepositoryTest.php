<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace Tests\Unit;

use Codeception\Test\Unit;
use Socodo\ORM\Attributes\AutoIncrement;
use Socodo\ORM\Attributes\Column;
use Socodo\ORM\Attributes\Primary;
use Socodo\ORM\Attributes\Table;
use Socodo\ORM\DB;
use Socodo\ORM\Model;
use Socodo\ORM\Repository;
use Tests\Support\UnitTester;

class RepositoryTest extends Unit
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
     * Repository::get()
     *
     * @return void
     */
    public function testGet (): void
    {
        $repository = new Repository(User::class);

        $get = $repository->get(0);
        $this->assertNull($get);

        $get = $repository->get(1);
        $this->tester->seeInDatabase('user', [ 'id' => $get->id, 'email_address' => $get->emailAddress ]);

        $get = $repository->get(2);
        $this->tester->seeInDatabase('user', [ 'id' => $get->id, 'email_address' => $get->emailAddress ]);
        $this->tester->seeInDatabase('profile', [ 'id' => $get->profile->id, 'name' => $get->profile->name, 'nick_name' => $get->profile->nickName ]);
    }

    /**
     * Repository::findOne()
     *
     * @return void
     */
    public function testFindOne (): void
    {
        $repository = new Repository(User::class);

        $find = $repository->findOne([ 'id' => 0 ]);
        $this->assertNull($find);

        $find = $repository->findOne([ 'id' => 1 ]);
        $this->tester->seeInDatabase('user', [ 'id' => $find->id, 'email_address' => $find->emailAddress ]);

        $find = $repository->findOne([ 'email_address' => $find->emailAddress ]);
        $this->tester->seeInDatabase('user', [ 'id' => $find->id, 'email_address' => $find->emailAddress ]);

        $find = $repository->findOne([]);
        $this->assertEquals(1, $find->id);
        $this->tester->seeInDatabase('user', [ 'id' => $find->id, 'email_address' => $find->emailAddress ]);
    }

    /**
     * Repository::find()
     *
     * @return void
     */
    public function testFind (): void
    {
        $repository = new Repository(User::class);

        $find = $repository->find();
        $this->assertIsIterable($find);
        foreach ($find as $user)
        {
            $this->tester->seeInDatabase('user', [ 'id' => $user->id, 'email_address' => $user->emailAddress ]);
        }
    }

    /**
     * Repository::save()
     *
     * @return void
     */
    public function testSave (): void
    {
        $repository = new Repository(User::class);

        $user = new User();
        $user->emailAddress = 'new@save.com';
        $this->assertTrue($repository->save($user));

        $user = $repository->get($user->id);
        $user->emailAddress = 'save@save.com';
        $this->assertTrue($repository->save($user));
        $this->tester->seeInDatabase('user', [ 'id' => $user->id, 'email_address' => $user->emailAddress ]);

        $user = new User();
        $user->emailAddress = 'profile@save.com';
        $user->profile = new Profile();
        $user->profile->name = 'Profile Doe';
        $user->profile->nickName = 'profile';
        $this->assertTrue($repository->save($user));
        $this->tester->seeInDatabase('user', [ 'id' => $user->id, 'email_address' => $user->emailAddress ]);
        $this->tester->seeInDatabase('profile', [ 'id' => $user->profile->id, 'name' => $user->profile->name, 'nick_name' => $user->profile->nickName ]);

        $user = User::from([
            'email_address' => 'from@save.com'
        ]);
        $this->assertTrue($repository->save($user));
        $this->tester->seeInDatabase('user', [ 'id' => $user->id, 'email_address' => $user->emailAddress ]);
    }
}

#[Table('user')]
class User extends Model
{
    #[Primary]
    #[AutoIncrement]
    public int $id;

    public string $emailAddress;

    #[Column('profile_id')]
    public ?Profile $profile = null;
}

#[Table('profile')]
class Profile extends Model
{
    #[Primary]
    #[AutoIncrement]
    public int $id;

    public string $name;

    public string $nickName;
}