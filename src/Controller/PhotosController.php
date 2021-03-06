<?php
 /**
 * Photos controller 
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
use Imagine\Image\Box;
use Imagine\Image\Point;
use Model\PhotosModel;
use Model\ProjectsModel;

/**
 * Class PhotosController
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
 * @uses Imagine\Image\Box
 * @uses Imagine\Image\Point
 * @uses Model\PhotosModel
 * @uses Model\ProjectsModel
 */
class PhotosController implements ControllerProviderInterface
{
    /**
     * PhotosModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

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
        $this->_model = new PhotosModel($app);
        $this->_project = new ProjectsModel($app);
        $photosController = $app['controllers_factory'];
        $photosController->get('/{page}/{idproject}/', array($this, 'index'))
            ->value('page', 1)
            ->bind('/photos/');
        $photosController->match('/upload/{idproject}', array($this, 'upload'))
            ->bind('/photos/upload');
        $photosController->match('/delete/{name}', array($this, 'delete'))
            ->bind('/photos/delete');
        $photosController->match('/manager/', array($this, 'manager'))
            ->bind('/photos/manager');
        return $photosController;
    }


    /**
     * View all photos for project
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function index(Application $app, Request $request)
    {
        $idproject = $id = (int)$request->get('idproject', 0);

        $check = $this->_project->checkProjectId($id);
        if ($check) {
            $photos = $this->_model->getPhotosByProject($idproject);
            return $app['twig']->render(
                'photos/index.twig', array(
                    'photos' => $photos
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
     * Upload photo
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function upload(Application $app, Request $request)
    {
        $idproject = $id = (int)$request->get('idproject', 0);

        $check = $this->_project->checkProjectId($idproject);
        if ($check) {

            $data = array(
                'idproject' => $idproject,
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'file', 'file', array(
                    'label' => 'Choose file',
                    'constraints' => array(
                        new Assert\File(
                            array(
                                'maxSize' => '1024k',
                                'mimeTypes' => array(
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                ),
                            )
                        )
                    )
                )
                )
                ->add(
                    'alt', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 3,
                                'max' => 45,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                                'maxMessage' => 
                                    'Maksymalna ilość znaków to{{ limit }}',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Tekst jest niepoprawny',
                            )
                        )
                    )
                )
                )
                ->add(
                    'save', 'submit', array(
                        'label' => 'Upload file'
                    )
                )
                ->getForm();
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {

                    try {

                        $files = $request->files->get($form->getName());
                        $data = $form->getData();

                        $path 
                            = dirname(dirname(dirname(__FILE__))) .
                                 '/web/media';

                        $originalFilename 
                            = $files['file']->getClientOriginalName();
                        $newFilename 
                            = $this->_model->createName($originalFilename);
                        $files['file']->move($path, $newFilename);

                        $this->_model->savePhoto($newFilename, $data);
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Zdjecie zostało pobrane'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/projects/'
                            ), 301
                        );

                    } catch (Exception $e) {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'danger',
                                'content' => 'Nie można pobrać zdjecia'
                            )
                        );
                    }

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'danger',
                            'content' => 'Dane niepoprawne'
                        )
                    );
                }
            }
            return $app['twig']->render(
                'photos/upload.twig', array(
                    'form' => $form->createView(),
                    'idproject' => $idproject
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono zdjęcia'
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
     * Delete photo
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @return mixed Generates page.
     */
    public function delete(Application $app, Request $request)
    {
        $name = (string)$request->get('name', 0);
        $check = $this->_model->checkPhotoName($name);
        if ($check) {
            $photo = $this->_model->getPhotoByName($name);
            $path = dirname(dirname(dirname(__FILE__))) . '/web/media/' . $name;

            if (count($photo)) {
                $data = array();
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'name', 'hidden', array(
                            'data' => $name,
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
                            $model = unlink($path);


                            try {
                                $link = $this->_model->removePhoto($name);

                                $app['session']->getFlashBag()->add(
                                    'message', array(
                                        'type' => 'success',
                                        'content' => 
                                            'Zdjecie zostało usunięte'
                                    )
                                );
                                return $app->redirect(
                                    $app['url_generator']->generate(
                                        '/photos/manager'
                                    ), 301
                                );
                            } catch (\Exception $e) {
                                $errors[] = 'Coś poszło niezgodnie z planem';
                            }
                        } catch (\Exception $e) {
                            $errors[] = 'Plik nie zstał usuniety';
                        }
                    }
                }

                return $app['twig']->render(
                    'photos/delete.twig', array(
                        'form' => $form->createView()
                    )
                );

            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono zdjęcia'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/photos/manager'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono zdjęcia'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/photos/manager'
                ), 301
            );

        }
    }

    /**
     * Photos control panel
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates Page.
     */
    public function manager(Application $app, Request $request)
    {
        $photos = $this->_model->getPhotos();

        return $app['twig']->render(
            'photos/manager.twig', array(
                'photos' => $photos
            )
        );

    }
}
