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
    protected UnitTester $tester;

    protected function _before()
    {
        new DB('127.0.0.1:3306', 'socodo', 'socodo', 'socodo');
    }

    /**
     * Repository::get()
     *
     * @return void
     */
    public function testGet(): void
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
}

#[Table('user')]
class User extends Model
{
    #[Primary]
    #[AutoIncrement]
    public int $id;

    public string $emailAddress;

    #[Column('profile_id')]
    public Profile $profile;
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