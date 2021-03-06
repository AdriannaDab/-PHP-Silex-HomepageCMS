<?php
 /**
 * Categories Model
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

use Silex\Application;

/**
 * Class CategoriesModel
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
class CategoriesModel
{

    /**
     * Database access object.
     *
     * @access protected
     * @var $_db Doctrine\DBAL
     */
    protected $_db;

    /**
     * Class constructor.
     *
     * @access public
     * @param Application $app Silex application object
     */
    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }

    /**
     * Gets one category.
     *
     * @param Integer $idCategory id category.
     *
     * @access public
     * @return Array Associative array contains information about tag.
     */
    public function getCategory($idCategory)
    {
        $sql = 'SELECT * FROM blog_categories WHERE idcategory = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idCategory));
    }

    public function getPostsListByIdcategory($id)
    {
        $sql = 'SELECT * 
            FROM blog_posts 
            natural join blog_categories 
            where idcategory = ?';
        return $this->_db->fetchAll($sql, array($id));
    }

    /**
     * Add category.
     *
     * @param  Array $data Associative array contains new category name.
     *
     * @access public
     * @return Void
     */
    public function addCategory($data)
    {
        $sql = 'INSERT INTO blog_categories (name) VALUES (?)';
        $this->_db->executeQuery($sql, array($data['name']));
    }

    /**
     * Updates name of category.
     *
     * @param Array $data Associative array contains id category and new name.
     *
     * @access public
     * @return Void
     */
    public function editCategory($data)
    {

        if (isset($data['idcategory']) 
            && ctype_digit((string)$data['idcategory'])) {
            $sql = 'UPDATE blog_categories SET name = ? WHERE idcategory = ?';
            $this->_db->executeQuery(
                $sql, 
                array(
                    $data['name'], 
                    $data['idcategory']
                )
            );
        } else {
            $sql = 'INSERT INTO blog_categories (idcategory, name) 
                VALUES (?,?)';
            $this->_db->executeQuery(
                $sql, array(
                        $data['idcategory'], 
                        $data['name']
                )
            );
        }
    }

    /**
     * Delete category.
     *
     * @param Array $data Associative array contains id category.
     *
     * @access public
     * @return Void
     */
    public function deleteCategory($data)
    {
        $sql = 'DELETE FROM `blog_categories` WHERE `idcategory`= ?';
        $this->_db->executeQuery($sql, array($data['idcategory']));
    }

    /**
     * Change key in categories array
     *
     * @access public
     * @return Array tags array.
     */

    public function getCategoriesDict()
    {
        $categories = $this->getCategories();
        $data = array();
        foreach ($categories as $row) {
            $data[$row['idcategory']] = $row['name'];
        }
        return $data;
    }


    /**
     * Gets all categories.
     *
     * @access public
     * @return Array Categories array.
     */
    public function getCategories()
    {
        $sql = 'SELECT * FROM blog_categories';
        return $this->_db->fetchAll($sql);
    }

    /**
     * Check if category id exists
     *
     * @param $idcategory id category from request
     *
     * @access public
     * @return bool True if exists.
     */
    public function checkCategoryId($idcategory)
    {
        $sql = 'SELECT * FROM blog_categories WHERE idcategory=?';
        $result = $this->_db->fetchAll($sql, array($idcategory));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
