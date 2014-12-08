<?php
 /**
 * Photos model 
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

/**
 * Class PhotosModel
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
class PhotosModel
{

    /**
     * Database access object.
     *
     * @var $_model
     * @access protected
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
        $this->_db = $app['db'];
    }

    /**
     * Save photo
     *
     * @param $name new file name
     * @param date array with informations about file
     *
     * @access public
     * @return void
     */
    public function savePhoto($name, $data)
    {
        $sql = 'INSERT INTO `project_photos` (`name`, `alt`, `idproject`) 
            VALUES (?,?,?)';
        $this->_db->executeQuery(
            $sql, array(
                $name, 
                $data['alt'], 
                $data['idproject']
            )
        );
    }

    /**
     * Create new name photo
     *
     * @param $name original name
     *
     * @access public
     * @return string file name
     */
    public function createName($name)
    {
        $newName = '';
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $newName = $this->_randomString(32) . '.' . $ext;

        while (!$this->_isUniqueName($newName)) {
            $newName = $this->_randomString(32) . '.' . $ext;
        }

        return $newName;
    }

    /**
     * Get photo by project id
     *
     * @param $idproject project id
     *
     * @access public
     * @return array array eith information about project
     */
    public function getPhotosByProject($idproject)
    {
        $sql = 'SELECT * FROM project_photos WHERE idproject = ?';
        return $this->_db->fetchAll($sql, array($idproject));
    }

    /**
     * Get all photos
     *
     * @access public
     * @return Array array with information about file
     */
    public function getPhotos()
    {
        $sql = 'SELECT * FROM project_photos NATURAL JOIN projects';
        return $this->_db->fetchAll($sql);
    }

    /**
     * Get photo by name
     *
     * @param $name file name
     *
     * @access public
     * @return mixed
     */
    public function getPhotoByName($name)
    {
        $sql = 'SELECT * FROM project_photos WHERE name=?';
        return $this->_db->fetchAssoc($sql, array($name));
    }

    /**
     * Remove photo
     *
     * @param $name file name
     *
     * @access public
     * @return void
     */
    public function removePhoto($name)
    {
        $sql = 'DELETE FROM `project_photos` WHERE name = ?';
        $this->_db->executeQuery($sql, array($name));
    }

    /**
     * Check if photo name exists
     *
     * @param $name file name
     *
     * @access public
     * @return bool true if exists
     */
    public function checkPhotoName($name)
    {
        $sql = 'SELECT * FROM project_photos WHERE name=?';
        $result = $this->_db->fetchAll($sql, array($name));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get random string
     *
     * @param integer $length number of how long shout be file name
     *
     * @access protected
     * @return string file name
     */
    protected function _randomString($length)
    {
        $string = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));
        for ($i = 0; $i < $length; $i++) {
            $string .= $keys[array_rand($keys)];
        }
        return $string;
    }


    /**
     * Check if name id unique
     *
     * @param $name
     *
     * @access public
     * @return bool
     */
    protected function _isUniqueName($name)
    {
        $sql = 'SELECT COUNT(*) AS files_count 
                FROM project_photos 
                WHERE name = ?';
        $result = $this->_db->fetchAssoc($sql, array($name));
        return !$result['files_count'];
    }
}
