<?php
/**
 * Authentication controller
 *
 * PHP version 5
 *
 * @category Controller
 * @package  Controller
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  SVN: $id$
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 */
 
namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;

/**
 * Class AuthController
 *
 * @category Controller
 * @package  Controller
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     wierzba.wzks.uj.edu.pl/~12_grudnik
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\UsersModel
 */
class AuthController implements ControllerProviderInterface
{

    /**
     * Connection
     *
     * @param Application $app application object
     *
     * @return \Silex\ControllerCollection
     */ 
    public function connect(Application $app)
    {
        $this->_model = new UsersModel($app);
        $authController = $app['controllers_factory'];
        $authController->match('/login', array($this, 'login'))
            ->bind('/auth/login');
        $authController->match('/logout', array($this, 'logout'))
            ->bind('/auth/logout');
        return $authController;
    }

    /**
     * Login
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page.
     */
    public function login(Application $app, Request $request)
    {
        $form = $app['form.factory']->createBuilder('form')
            ->add(
                'username', 'text', array(
                    'label' => 'Username',
                    'data' => $app['session']->get('_security.last_username')
                )
            )
            ->add('password', 'password', array('label' => 'Password'))
            ->getForm();

        return $app['twig']->render(
            'auth/login.twig', array(
                'form' =>$form->createView(), 
                'error' =>$app['security.last_error']($request) 
                )
        ); 
    }

    /**
     * logout
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page.
     */
    public function logout(Application $app, Request $request)
    {
        $app['session']->clear();
        return $app['twig']->render('auth/logout.twig');
    }
}
