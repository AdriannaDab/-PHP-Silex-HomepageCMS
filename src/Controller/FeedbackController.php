<?php
 /**
 * Feedback controller 
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
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\FeedbackModel;
use Model\UsersModel;
use Model\ProjectsModel;

/**
 * Class FeedbackController
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
 * @uses Model\FeedbackModel;
 * @uses Model\UsersModel;
 * @uses Model\ProjectsModel;
 */
class FeedbackController implements ControllerProviderInterface
{
    /**
     * FeedbackModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * UsersModel object.
     *
     * @var $_user
     * @access protected
     */
    protected $_user;

    /**
     * ProjectsModel object.
     *
     * @var $_project
     * @access protected
     */
    protected $_project;


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
        $this->_model = new feedbackModel($app);
        $this->_user = new UsersModel($app);
        $this->_project = new ProjectsModel($app);
        $feedbackController = $app['controllers_factory'];
        $feedbackController->get('/{page}/{idproject}/', array($this, 'index'))
            ->value('page', 1)
            ->bind('/feedback/');
        $feedbackController->match('/add/{idproject}', array($this, 'add'))
            ->bind('/feedback/add');
        $feedbackController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/feedback/edit');
        $feedbackController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/feedback/delete');
        return $feedbackController;
    }

    /**
     * View all feedback for project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function index(Application $app, Request $request)
    {
        $id = (int)$request->get('idproject', 0);

        $check = $this->_project->checkProjectId($id);

        if ($check) {

            $_isLogged = $this->_user->_isLoggedIn($app);
            if ($_isLogged) {
                $access = $this->_user->getIdCurrentUser($app);
            } else {
                $access = 0;
            }
            $feedback = $this->_model->getFeedbackList($id);
            return $app['twig']->render(
                'feedback/index.twig', array(
                    'feedback' => $feedback, 
                    'idproject' => $id, 
                    'access' => $access
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono feedbacku'
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
     * Add new feedback
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function add(Application $app, Request $request)
    {

        $idproject = (int)$request->get('idproject', 0);

        $check = $this->_project->checkProjectId($idproject);

        if ($check) {
            $iduser = $this->_user->getIdCurrentUser($app);

            $data = array(
                'published_date' => date('Y-m-d'),
                'idproject' => $idproject,
                'iduser' => $iduser,
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'content', 'textarea', array(
                        'required' => false), array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 3,
                                    'minMessage' => 
                                        'Minimalna ilość znaków to 3',
                                )
                            ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Tekst nie jest poprawny',
                            )
                        )
                    )
                )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $model = $this->_model->addFeedback($data);
                if (!$model) {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Feedback został dodany'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/projects/'
                        ), 301
                    );
                }
            }
            return $app['twig']->render(
                'feedback/add.twig', array(
                    'form' => $form->createView()
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono feedbacku'
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
     * Edit feedback
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function edit(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkFeedbackId($id);

        if ($check) {
            $idCurrentUser = $this->_user->getIdCurrentUser($app);
            $feedback = $this->_model->getFeedback($id);


            if (count($feedback)) {
                $data = array(
                    'idfeedback' => $id,
                    'published_date' => date('Y-m-d'),
                    'idproject' => $feedback['idproject'],
                    'iduser' => $feedback['iduser'],
                    'idCurrentUser' => $idCurrentUser,
                    'content' => $feedback['content'],
                );

                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'content', 'textarea', array(
                            'required' => false), array(
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Length(
                                        array(
                                        'min' => 3,
                                        'minMessage' => 
                                            'Minimalna ilość znaków to 3',
                                        )
                                    ),
                                    new Assert\Type(
                                        array(
                                            'type' => 
                                                'string',
                                            'message' => 
                                                'Tekst nie jest poprawny',
                                        )
                                    )
                        )
                    )
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $model = $this->_model->editFeedback($data);
                    if (!$model) {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Feedback został zmieniony'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/projects/'
                            ), 301
                        );

                    }
                }
                return $app['twig']->render(
                    'feedback/edit.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono feedbacku'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/feedback/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono feedbacku'
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
     * Delete feedback
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function delete(Application $app, Request $request)
    {
        $id = (int)$request->get('id', 0);


        $check = $this->_model->checkFeedbackId($id);

        if ($check) {
            $feedback = $this->_model->getFeedback($id);
            $data = array();

            if (count($feedback)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idfeedback', 'hidden', array(
                        'data' => $id,
                        )
                    )
                    ->add('Yes', 'submit')
                    ->add('No', 'submit')
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    if ($form->get('Yes')->isClicked()) {
                        $data = $form->getData();
                        try {
                            $model = $this->_model->deleteFeedback($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Feedback został usunięty'
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
                    } else {
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/posts/'
                            ), 301
                        );
                    }
                }

                return $app['twig']->render(
                    'feedback/delete.twig', array(
                        'form' => $form->createView()
                    )
                );

            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono feedbacku'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/projects/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono feedbacku'
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
