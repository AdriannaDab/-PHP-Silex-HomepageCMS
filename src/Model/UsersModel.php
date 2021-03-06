<?php
 /**
 * Users model
 *
 * PHP version 5
 *
 * @category Model
 * @package  Model
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  SVN: $id$
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 */
namespace Model;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class UsersModel
 *
 * @category Model
 * @package  Model
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class UsersModel
{

    /**
     * Silex application object
     *
     * @access protected
     * @var $_app Silex\Application
     */
    protected $_app;
    /**
     * Database access object.
     *
     * @access protected
     * @var $_db Doctrine\DBAL
     */
    protected $_db;

    /**
     * Constructor
     *
     * @param Application $app
     *
     * @access public
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->_app = $app;
        $this->_db = $app['db'];
    }

    /**
     *
     * Get information about user
     *
     * @param $id uder id
     *
     * @access public
     * @return array Associative array with information about user
     */
    public function getUser($id)
    {
        $sql = "SELECT * FROM users WHERE iduser=?";
        return $this->_db->fetchAssoc($sql, array((int)$id));
    }

    /**
     * Puts one user to database.
     *
     * @param  Array $data Associative array contains all necessary information
     *
     * @access public
     * @return Void
     */
    public function register($data)
    {
        $check = $this->getUserByLogin($data['nickname']);

        if (!$check) {
            $users = "INSERT INTO `users` 
                (`nickname`, `email`, `homesite`, `signupdate`, `password`)
            VALUES (?,?,?,?,?);";
            $this->_db
                ->executeQuery(
                    $users,
                    array(
                        $data['nickname'],
                        $data['email'],
                        $data['homesite'],
                        $data['signupdate'],
                        $data['password'])
                );

            $sql = "SELECT * 
                    FROM users 
                    WHERE nickname ='" . $data['nickname'] . "';";
            $user = $this->_db->fetchAssoc($sql);

            $addRole = 'INSERT INTO users_roles ( iduser, idrole ) 
                VALUES(?, ?)';
            $this->_db->executeQuery($addRole, array($user['iduser'], 2));
        }
    }

    /**
     * Updates information about user.
     *
     * @param Array $data Associative array contains all necessary information
     *
     * @access public
     * @return Void
     */
    public function editUser($data)
    {
        if (isset($data['iduser']) && ctype_digit((string)$data['iduser'])) {
            $sql = 'UPDATE users SET email = ?,  homesite = ? WHERE iduser = ?';
            $this->_db->executeQuery(
                $sql, array(
                    $data['email'], 
                    $data['homesite'], 
                    $data['iduser']
                )
            );
        } else {
            $sql = 'INSERT INTO `users` 
                (`email`, `homesite`, `signupdate`, `password`)
            VALUES (?,?,?,?,?);';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['nickname'],
                        $data['email'],
                        $data['homesite'],
                        $data['signupdate'],
                        $data['password'],)
                );
        }
    }

    /**
     * Delete user
     *
     * @param Integer $id user id
     *
     * @access public
     * @return bool true if deleted
     */
    public function deleteUser($id)
    {
        if (isset($id) && ctype_digit((string)$id)) {
            $sql = 'DELETE FROM users_roles WHERE iduser = ?';
            $success = $this->_db->executeQuery($sql, array($id));

            if ($success) {
                $sqlTwo = 'DELETE FROM users 
                        WHERE iduser = ?';
                $successTwo = $this->_db->executeQuery($sqlTwo, array($id));

                if ($successTwo) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
    }

    public function changePassword($data, $id)
    {
        $sql = 'UPDATE `users` SET `password`=? WHERE `iduser`= ?';

        $this->_db->executeQuery($sql, array($data['new_password'], $id));
    }

    /**
     * Load user by login.
     *
     * @param String $login
     *
     * @access public
     * @return array
     */
    public function loadUserByLogin($login)
    {
        $data = $this->getUserByLogin($login);

        if (!$data) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $roles = $this->getUserRoles($data['iduser']);

        if (!$roles) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $user = array(
            'login' => $data['nickname'],
            'password' => $data['password'],
            'roles' => $roles
        );

        return $user;
    }

    /**
     * Gets user by id.
     *
     * @param Integer $id
     *
     * @access public
     * @return Array Information about searching user.
     */
    public function getUserById($id)
    {
        $sql = 'SELECT * FROM users WHERE `iduser` = ? Limit 1';
        return $this->_db->fetchAssoc($sql, array((int)$id));
    }

    /**
     * Get user by login.
     *
     * @param String $login
     *
     * @access public
     * @return Array Information about searching user.
     */
    public function getUserByLogin($login)
    {
        $sql = 'SELECT * FROM users WHERE nickname = ?';
        return $this->_db->fetchAssoc($sql, array((string)$login));
    }

    /**
     * Get users role.
     *
     * @param String $userId
     *
     * @access public
     * @return Array
     */
    public function getUserRoles($userId)
    {
        $sql = '
            SELECT
                roles.role
            FROM
                users_roles
            INNER JOIN
                roles
            ON users_roles.idrole=roles.idrole
            WHERE
                users_roles.iduser = ?
            ';

        $result = $this->_db->fetchAll($sql, array((string)$userId));

        $roles = array();
        foreach ($result as $row) {
            $roles[] = $row['role'];
        }

        return $roles;
    }

    /**
     * Connected user with his role.
     *
     * @param  Integer $iduser
     *
     * @access public
     * @return Void
     */
    public function addRole($iduser)
    {
        $sql = 'INSERT INTO `users_roles` (`iduser`, `idrole`) VALUES (?,?);';

        $this->_db->executeQuery($sql, array($iduser, '2'));
    }

    /**
     * Confirm user. Change his role
     *
     * @param  Integer $id
     *
     * @access public
     * @return Void
     */
    public function confirmUser($id)
    {
        $sql = 'UPDATE `users_roles` SET `idrole`="2" WHERE `iduser`= ?;';

        $this->_db->executeQuery($sql, array($id));
    }

    /**
     * Get current logged user id
     *
     * @param $app
     *
     * @access public
     * @return mixed
     */
    public function getIdCurrentUser($app)
    {

        $login = $this->getCurrentUser($app);
        $iduser = $this->getUserByLogin($login);

        return $iduser['iduser'];


    }

    /**
     * Get information about actual logged user
     *
     * @param $app
     *
     * @access protected
     * @return mixed
     */
    protected function getCurrentUser($app)
    {
        $token = $app['security']->getToken();

        if (null !== $token) {
            $user = $token->getUser()->getUsername();
        }

        return $user;
    }

    /**
     * Check if user is logged
     *
     * @param Application $app
     *
     * @access public
     * @return bool
     */
    public function _isLoggedIn(Application $app)
    {
        if ('anon.' !== $user = $app['security']->getToken()->getUser()) {
            return true;
        } else {
            return false;
        }
    }
}
