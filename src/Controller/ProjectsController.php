<?php
 /**
 * Projects controller 
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
use Model\ProjectsModel;
use Model\PhotosModel;

/**
 * Class ProjectsController
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
 * @uses Model\ProjectModel
 */
class ProjectsController implements ControllerProviderInterface
{
    /**
     * ProjectsModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * PhotossModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_photos;


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
        $this->_model = new ProjectsModel($app);
        $this->_photos = new PhotosModel($app);
        $projectController = $app['controllers_factory'];
        $projectController->get('/{page}', array($this, 'index'))
            ->value('page', 1)
            ->bind('/projects/');
        $projectController->match('/add/', array($this, 'add'))
            ->bind('/projects/add');
        $projectController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/projects/edit');
        $projectController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/projects/delete');
        $projectController->get('/view/{id}', array($this, 'view'))
            ->bind('/projects/view');
        return $projectController;
    }

    /**
     * View all projects
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page.
     */
    public function index(Application $app, Request $request)
    {
        $pageLimit = 1;
        $page = (int)$request->get('page', 1);
        $pagesCount = $this->_model->countProjectsPages($pageLimit);
        if (($page < 1) || ($page > $pagesCount)) {
            $page = 1;
        }
        $projects = $this->_model
            ->getProjectsPage($page, $pageLimit, $pagesCount);
        $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
        return $app['twig']->render(
            'projects/index.twig', array(
                'projects' => $projects, 
                'paginator' => $paginator
            )
        );
    }

    /**
     * Add new project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function add(Application $app, Request $request)
    {
        $data = array();
        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'title', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'max' => 45,
                            'minMessage' => 
                                'Minimalna ilość znaków to 3',
                            'maxMessage' => 
                                'Maksymalna ilość znaków to {{ limit }}',
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
            ->add(
                'description', 'textarea', array(
                    'required' => false), array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' => 
                                        'Minimalna ilość znaków to 5',
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

            try {
                $model = $this->_model->addProject($data);

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Projekt został dodany'
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
            'projects/add.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Edit project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function edit(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkProjectId($id);

        if ($check) {
            $project = $this->_model->getProject($id);

            // default values:
            $data = array(
                'title' => $project['title'],
                'description' => $project['description'],
            );

            if (count($project)) {
                $form = $app['form.factory']->createNamedBuilder('form', $data)
                    ->add(
                        'id', 'hidden', array(
                        'data' => $id,
                        )
                    )
                    ->add(
                        'title', 'text', array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 3,
                                    'max' => 45,
                                    'minMessage' => 
                                        'Minimalna ilość znaków to 3',
                                    'maxMessage' => 
                                        'Maksymalna ilość znaków to 45',
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
                    ->add(
                        'description', 'textarea', array(
                            'required' => false), array(
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Length(
                                        array(
                                            'min' => 5,
                                            'minMessage' => 
                                                'Min ilość znaków to 3',
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

                    try {
                        $model = $this->_model->editProject($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Project został zmieniony'
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
                    'projects/edit.twig', array(
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
                        '/projects/add'
                    ), 301
                );
            }
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
     * Delete project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function delete(Application $app, Request $request)
    {
        $id = (int)$request->get('id', 0);
        $check = $this->_model->checkProjectId($id);

        if ($check) {
            $project = $this->_model->getProject($id);
            $data = array();

            if (count($project)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idproject', 'hidden', array(
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
                            $model = $this->_model->deleteProject($data);

                            $photos = $this->_photos
                                ->getPhotosByProject($data['idproject']);

                            foreach ($photos as $photo) {
                                $path 
                                    = dirname(dirname(dirname(__FILE__))).
                                        '/web/media/'.$photo['name'];
                                unlink($path);
                                $model 
                                    = $this->_photos
                                        ->removePhoto($photo['name']);
                            }


                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Projekt został usunięty'
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
                                '/projects/'
                            ), 301
                        );
                    }

                } else {
                    return $app['twig']->render(
                        'projects/delete.twig', array(
                            'form' => $form->createView()
                        )
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono projektu'
                    )
                );
                $app['session']->getFlashBag()->set(
                    'error', 'Project not found'
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
     * View project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function view(Application $app, Request $request)
    {
        $id = (int)$request->get('id', 0);
        $check = $this->_model->checkProjectId($id);

        if ($check) {
            $project = $this->_model->getProject($id);
            if (count($project)) {
                return $app['twig']->render(
                    'projects/view.twig', array(
                        'project' => $project
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
}
