<?php
 /**
 * Blog posts model
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
 * Class PostsModel
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
class PostsModel
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
     */
    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }

    /**
     * Gets one post.
     *
     * @param Integer $idpost post id
     *
     * @access public
     * @return Array Associative array contains information about post
     */
    public function getPost($idpost)
    {
        $sql = 'SELECT * FROM blog_posts WHERE idpost = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idpost));
    }

    /**
     * Gets all posts
     *
     * @access public
     * @return Array Posts array
     */
    public function getPostList()
    {
        $sql = 'SELECT * FROM blog_posts';
        return $this->_db->fetchAll($sql);
    }

    /**
     * Puts one post.
     *
     * @param  Array $data Associative array information about category
     *
     * @access public
     * @return Void
     */
    public function addPost($data)
    {
        $sql = 'INSERT INTO blog_posts 
            (title, content, published_date, idcategory) 
            VALUES (?,?,?,?)';
        $this->_db
            ->executeQuery(
                $sql, 
                array(
                    $data['title'], 
                    $data['content'], 
                    $data['published_date'], 
                    $data['category']
                )
            );
    }

    /**
     * Updates one post.
     *
     * @param Array $data array with informations about post
     *
     * @access public
     * @return Void
     */
    public function editPost($data)
    {

        if (isset($data['id']) && ctype_digit((string)$data['id'])) {
            $sql = 'UPDATE blog_posts 
                    SET title = ?, content = ?, 
                        published_date = ? , idcategory = ? 
                    WHERE idpost = ?';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['title'], 
                        $data['content'], 
                        $data['published_date'], 
                        $data['category'], 
                        $data['id']
                    )
                );
        } else {
            $sql = 'INSERT INTO blog_posts 
                    (title, content, published_date, idcategory) 
                    VALUES (?,?,?,?)';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['title'], 
                        $data['content'], 
                        $data['published_date'], 
                        $data['category']
                    )
                );
        }
    }

    /**
     * Delete one post.
     *
     * @param Array $data Associative array contains id post
     *
     * @access public
     * @return Void
     */
    public function deletePost($data)
    {
        $post = 'DELETE FROM `blog_posts` WHERE `idpost`= ?';
        $this->_db->executeQuery($post, array($data['idpost']));

        $comments = 'DELETE FROM `blog_comments` WHERE `idpost`= ?';
        $this->_db->executeQuery($comments, array($data['idpost']));

        $tags = 'DELETE FROM `blog_posts_tags` WHERE `idpost`= ?';
        $this->_db->executeQuery($tags, array($data['idpost']));
    }

    /**
     * Gets one post with his category name
     *
     * @param Integer $idpost post id
     *
     * @access public
     * @return Array Associative post array
     */
    public function getPostWithCategoryName($idpost)
    {
        $sql = 'SELECT * 
                FROM blog_posts 
                natural join blog_categories 
                where idpost = ? 
                LIMIT 1';

        return $this->_db->fetchAssoc($sql, array($idpost));
    }

    /**
     * Count amount of posts
     *
     * @param Integer $limit number of post on page
     *
     * @access public
     * @return Integer number of page
     */
    public function countPostsPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM blog_posts';
        $result = $this->_db->fetchAssoc($sql);
        if ($result) {
            $pagesCount = ceil($result['pages_count'] / $limit);
        }
        return $pagesCount;
    }

    /**
     *Get post page
     *
     * @param Integer $page
     * @param Integer $limit number of post on page
     * @param Integer $pagesCount
     *
     * @access public
     * @return Array number of page
     */
    public function getPostsPage($page, $limit, $pagesCount)
    {
        if (($page <= 1) || ($page > $pagesCount)) {
            $page = 1;
        }
        $sql = 'SELECT * 
                FROM blog_posts 
                natural join blog_categories 
                LIMIT :start, :limit';
        $statement = $this->_db->prepare($sql);
        $statement->bindValue('start', ($page - 1) * $limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Check if post id exists
     *
     * @param $idpost post id
     *
     * @access public
     * @return bool true if exists
     */
    public function checkPostId($idpost)
    {
        $sql = 'SELECT * FROM blog_posts WHERE idpost=?';
        $result = $this->_db->fetchAll($sql, array($idpost));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
