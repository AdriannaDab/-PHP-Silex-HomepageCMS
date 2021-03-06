<?php
 /**
 * Feedback model
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
 * Class FeedbackModel
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
class FeedbackModel
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
     * Gets one feedback.
     *
     * @param Integer $idfeedback feedback id
     *
     * @access public
     * @return Array Associative array contains information about feedback.
     */
    public function getFeedback($idfeedback)
    {
        $sql = 'SELECT * FROM project_feedback WHERE idfeedback = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idfeedback));
    }

    /**
     * Gets all feedback for one project
     *
     * @param Integer $id project id
     *
     * @access public
     * @return Array Feedback array
     */
    public function getFeedbackList($id)
    {
        $sql = 'SELECT * FROM project_feedback WHERE idproject = ?';
        return $this->_db->fetchAll($sql, array($id));
    }

    /**
     * Puts one feedback.
     *
     * @param  Array $data Associative array contains information about feedback
     *
     * @access public
     * @return Void
     */
    public function addFeedback($data)
    {
        $sql = 'INSERT INTO project_feedback 
            (content, published_date, idproject, iduser) 
        VALUES (?,?,?,?)';
        $this->_db
            ->executeQuery(
                $sql, 
                array(
                    $data['content'], 
                    $data['published_date'], 
                    $data['idproject'], 
                    $data['iduser']
                )
            );
    }

    /**
     * Updates one feedback.
     *
     * @param Array $data Associative array contains information about feedback
     *
     * @access public
     * @return Void
     */
    public function editFeedback($data)
    {

        if (isset($data['idfeedback']) 
        && ctype_digit((string)$data['idfeedback'])) {
            $sql = 'UPDATE project_feedback 
                SET content = ?, published_date = ? 
            WHERE idfeedback = ?';
            $this->_db->executeQuery(
                $sql, array(
                    $data['content'], 
                    $data['published_date'], 
                    $data['idfeedback']
                )
            );
        } else {
            $sql = 'INSERT INTO project_feedback 
                (content, published_date, idproject, iduser) 
            VALUES (?,?,?,?)';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['content'], 
                        $data['published_date'], 
                        $data['idproject'], 
                        $data['idCurrentUser']
                    )
                );
        }
    }

    /**
     * Delete one comment.
     *
     * @param Array $data Associative array contains  id feedback
     *
     * @access public
     * @return Void
     */
    public function deleteFeedback($data)
    {
        $sql = 'DELETE FROM `project_feedback` WHERE `idfeedback`= ?';
        $this->_db->executeQuery($sql, array($data['idfeedback']));
    }

    /**
     * Check if feedback id exists
     *
     * @param $idfeedback feddback id.
     *
     * @access public
     * @return bool True if exists.
     */
    public function checkFeedbackId($idfeedback)
    {
        $sql = 'SELECT * FROM project_feedback WHERE idfeedback=?';
        $result = $this->_db->fetchAll($sql, array($idfeedback));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
