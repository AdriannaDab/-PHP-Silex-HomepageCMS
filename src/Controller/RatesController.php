<?php
 /**
 * Rates controller 
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
use Model\RatesModel;
use Model\UsersModel;
use Model\ProjectsModel;

/**
 * Class RatesController
 *
 * @category Controller
 * @package  Controller
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\RatesModel
 * @uses Model\ProjectsModel
 * @uses Model\UsersModel
 */
class RatesController implements ControllerProviderInterface
{
    /**
     * RatesModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * ProjectModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_project;

    /**
     * UsersModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_user;

    /**
     * Connection
     *
     * @param Application $app application object
     * 
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new RatesModel($app);
        $this->_user = new UsersModel($app);
        $this->_project = new ProjectsModel($app);
        $rateController = $app['controllers_factory'];
        $rateController->match('/add/{idproject}', array($this, 'add'))
            ->bind('/rates/add');
        $rateController->match('/view/{idproject}', array($this, 'generalRate'))
            ->bind('/rates/view');
        return $rateController;
    }


    /**
     * Add rate
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function add(Application $app, Request $request)
    {
        $idproject = (int)$request->get('idproject', 0);

        $check = $this->_project->checkProjectId($idproject);

        if ($check) {

            $iduser = $this->_user->getIdCurrentUser($app);

            $check = $this->_model->checkAccess($idproject, $iduser);

            if ($check) {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 
                            'Już dodałes ocene. 
                                Ponowne dodanie oceny jest niemożliwe.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/projects/'
                    ), 301
                );
            }
            $data = array(
                'published_date' => date('Y-m-d'),
                'idproject' => $idproject,
                'iduser' => $iduser,
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'rate', 'choice', array(
                        'choices' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                        ),
                    )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $model = $this->_model->addRate($data);

                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Ocena została dodana'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/projects/'
                        ), 301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło niezgodnie z planem';
                }
            }

            return $app['twig']->render(
                'rates/add.twig', array(
                    'form' => $form->createView()
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono projektu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/projects/'
                ), 301
            );

        }
    }


    /**
     * View general Rate for project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function generalRate(Application $app, Request $request)
    {
        $idproject = (int)$request->get('idproject', 0);

        $check = $this->_project->checkProjectId($idproject);

        if ($check) {
            $rate = $this->_model->getGeneralRate($idproject);

            $generalRate = number_format($rate['general'], 2);

            return $app['twig']->render(
                'rates/view.twig', array(
                    'idproject' => $idproject, 
                    'rate' => $generalRate
                )
            );

        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono statystyki'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/projects/'
                ), 301
            );
        }
    }
}
