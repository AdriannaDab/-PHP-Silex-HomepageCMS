<?php
 /**
 * Rates model 
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
 * Class RatesModel
 *
 * @category Controller
 * @package  Controller
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class RatesModel
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
     * Gets one rate.
     *
     * @param Integer $idRate rate id
     *
     * @access public
     * @return Array Associative array contains information about rate.
     */
    public function getRate($idRate)
    {
        $sql = 'SELECT * FROM project_ratings WHERE id = ? LIMIT 1';

        return $this->_db->fetchAssoc($sql, array($idRate));
    }

    /**
     * Gets general projects rate
     *
     * @param Integer $idproject project id
     *
     * @access public
     * @return Array Associative array with general rate.
     */
    public function getGeneralRate($idproject)
    {
        $sql = 'SELECT avg(rate) as general 
                FROM project_ratings 
                where idproject = ? 
                LIMIT 1;';
        return $this->_db->fetchAssoc($sql, array($idproject));
    }

    /**
     * Add rate
     *
     * @param  Array $data array with information about rate
     *
     * @access public
     * @return Void
     */
    public function addRate($data)
    {
        $sql = 'INSERT INTO project_ratings 
            (rate, published_date, idproject, iduser) 
        VALUES (?,?,?,?)';
        $this->_db
            ->executeQuery(
                $sql, array(
                    $data['rate'], 
                    $data['published_date'], 
                    $data['idproject'], 
                    $data['iduser']
                )
            );
    }

    /**
     * Check if user added his rate already.
     *
     * @param Integer $idproject project id
     * @param Integer $iduser Current user id
     *
     * @access public
     * @return Array projects array with general rates.
     */
    public function checkAccess($idproject, $iduser)
    {
        $sql = 'SELECT * 
                FROM project_ratings 
                where idproject = ? && iduser = ?;';
        $result = $this->_db->fetchAll($sql, array($idproject, $iduser));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
