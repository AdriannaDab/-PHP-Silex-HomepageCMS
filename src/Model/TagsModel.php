<?php
 /**
 * Tags model
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
 * Class TagsModel
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
class TagsModel
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
     * @param Application $app Silex application object
     *
     * @access public
     */
    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }

    /**
     * Gets one tag.
     *
     * @param Integer $idTag tag id
     *
     * @access public
     * @return Array Associative array contains information about tag.
     */
    public function getTag($idTag)
    {
        $sql = 'SELECT * FROM blog_tags WHERE idtag = ? LIMIT 1';

        return $this->_db->fetchAssoc($sql, array($idTag));
    }

    /**
     * Puts tag.
     *
     * @param  Array $data Associative array contains new tags title
     *
     * @access public
     * @return Void
     */
    public function addTag($data)
    {
        $sql = 'INSERT INTO blog_tags (title) VALUES (?)';
        $this->_db->executeQuery($sql, array($data['title']));
    }

    /**
     * Updates name of tag.
     *
     * @param Array $data Associative array contains id tag and new title
     *
     * @access public
     * @return Void
     */
    public function editTag($data)
    {

        if (isset($data['idtag']) && ctype_digit((string)$data['idtag'])) {
            $sql = 'UPDATE blog_tags SET title = ? WHERE idtag = ?';
            $this->_db->executeQuery(
                $sql, array(
                    $data['title'], 
                    $data['idtag']
                )
            );
        } else {
            $sql = 'INSERT INTO blog_tags (idtag, title) VALUES (?,?)';
            $this->_db->executeQuery(
                $sql, array(
                    $data['idtag'], 
                    $data['title']
                )
            );
        }
    }

    /**
     * Delete tag.
     *
     * @param Array $data Associative array contains id tag
     *
     * @access public
     * @return Void
     */
    public function deleteTag($data)
    {
        $sql = 'DELETE FROM `blog_tags` WHERE `idtag`= ?';
        $this->_db->executeQuery($sql, array($data['idtag']));
    }

    /**
     * Gets all tags for post.
     *
     * @param Integer $id Post id
     *
     * @access public
     * @return Array Tags array.
     */
    public function getTagsListByPost($id)
    {
        $sql = 'SELECT * 
                FROM blog_posts_tags 
                natural join blog_tags 
                WHERE idpost = ?';
        return $this->_db->fetchAll($sql, array($id));
    }

    /**
     * Change key in tags array
     *
     * @access public
     * @return Array tags array.
     */
    public function getTagsDict()
    {
        $categories = $this->getTagList();
        $data = array();
        foreach ($categories as $row) {
            $data[$row['idtag']] = $row['title'];
        }
        return $data;
    }

    /**
     * Connect tag with post
     *
     * @param Array $data Array contains id post and id tag
     *
     * @access public
     * @return Void
     */
    public function connectWithPost($data)
    {
        $sql = 'INSERT INTO `blog_posts_tags` (`idpost`, `idtag`) 
                VALUES (?, ?);';

        $this->_db->executeQuery($sql, array($data['idpost'], $data['idtag']));
    }

    /**
     * Disconnect tag with post
     *
     * @param Array $data Array contains id post and id tag
     *
     * @access public
     * @return Void
     */
    public function disconnectWithPost($data)
    {
        $sql = 'DELETE FROM `blog_posts_tags` WHERE `idpost`= ? && `idtag`= ?;';
        $this->_db->executeQuery($sql, array($data['idpost'], $data['idtag']));
    }

    /**
     * Gets general rates for all posts
     *
     * @access public
     * @return Array tags array.
     */
    public function getTagList()
    {
        $sql = 'SELECT * FROM blog_tags';
        return $this->_db->fetchAll($sql);
    }

    /**
     * Check if tags id exists
     *
     * @param $idtag tag id
     *
     * @access public
     * @return bool true if exists
     */
    public function checkTagId($idtag)
    {
        $sql = 'SELECT * FROM blog_tags WHERE idtag=?';
        $result = $this->_db->fetchAll($sql, array($idtag));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
