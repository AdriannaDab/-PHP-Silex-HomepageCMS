<?php
 /**
 * Tags controller 
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
use Model\TagsModel;
use Model\PostsModel;

/**
 * Class TagsController
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
 * @uses Model\TagsModel;
 * @uses Model\PostsModel;
 */
class TagsController implements ControllerProviderInterface
{
    /**
     * TagsModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * PostsModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_posts;

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
        $this->_model = new TagsModel($app);
        $this->_posts = new PostsModel($app);
        $tagController = $app['controllers_factory'];
        $tagController->get('/{page}/{idpost}/', array($this, 'index'))
            ->value('page', 1)
            ->bind('/tags/');
        $tagController->match('/add/', array($this, 'add'))
            ->bind('/tags/add');
        $tagController->match('/edit/{idtag}', array($this, 'edit'))
            ->bind('/tags/edit');
        $tagController->match('/delete/{idtag}', array($this, 'delete'))
            ->bind('/tags/delete');
        $tagController->match('/connect/{idpost}', array($this, 'connectTag'))
            ->bind('/tags/connect');
        $tagController->match(
            '/disconnect/{idpost}/{idtag}', array(
                $this, 'disconnectTag'
            )
        )
            ->bind('/tags/disconnect');
        $tagController->match('/manage/{idpost}', array($this, 'manageTag'))
            ->bind('/tags/manage');
        $tagController->match('/controlpanel/', array($this, 'controlTag'))
            ->bind('/tags/controlpanel');
        return $tagController;
    }

    /**
     * View all tags for post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function index(Application $app, Request $request)
    {
        $id = (int)$request->get('idpost', 0);
        $tags = $this->_model->getTagsListByPost($id);

        return $app['twig']->render(
            'tags/index.twig', array(
                'tags' => $tags, 
                'idpost' => $id
            )
        );
    }

    /**
     * Add tag
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
                                'Maksymalna ilość znaków to {{ limit }}'
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
                $model = $this->_model->addTag($data);

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Tag został dodany'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/'
                    ), 301
                );
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło niezgodnie z planem';
            }
        }

        return $app['twig']->render(
            'tags/add.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Edit tag
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function edit(Application $app, Request $request)
    {
        $id = (int)$request->get('idtag', 0);
        $checkTag = $this->_model->checkTagId($id);

        if ($checkTag) {
            $tag = $this->_model->getTag($id);

            $data = array(
                'title' => $tag['title'],
                'idtag' => $id
            );

            if (count($tag)) {
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
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $model = $this->_model->editTag($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Tag został zmieniony'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/posts/'
                            ), 301
                        );
                    } catch (\Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }

                return $app['twig']->render(
                    'tags/edit.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono tagu'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/tags/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono tagu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Delete tag
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function delete(Application $app, Request $request)
    {
        $id = (int)$request->get('idtag', 0);
        $checkTag = $this->_model->checkTagId($id);

        if ($checkTag) {
            $tag = $this->_model->getTag($id);

            $data = array();

            if (count($tag)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idtag', 'hidden', array(
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
                            $model = $this->_model->deleteTag($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Tag został usunięty'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/posts/'
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
                    'tags/delete.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono tagu'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono tagu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Connect tag with post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function connectTag(Application $app, Request $request)
    {
        $idpost = (int)$request->get('idpost', 0);
        $checkPost = $this->_posts->checkPostId($idpost);

        if ($checkPost) {
            $tags = $this->_model->getTagsDict();

            $data = array();

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'idtag', 'choice', array(
                        'choices' => $tags,
                    )
                )
                ->add(
                    'idpost', 'hidden', array(
                        'data' => $idpost,
                    )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $model = $this->_model->connectWithPost($data);

                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Tag został dodany'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/posts/'
                        ), 301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło niezgodnie z planem';
                }
            }

            return $app['twig']->render(
                'tags/connect.twig', array(
                    'form' => $form->createView()
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono postu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Disconnected tag with post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function disconnectTag(Application $app, Request $request)
    {
        $idpost = (int)$request->get('idpost', 0);
        $checkPost = $this->_posts->checkPostId($idpost);

        if ($checkPost) {
            $idtag = (int)$request->get('idtag', 0);
            $checkTag = $this->_model->checkTagId($idtag);

            if ($checkTag) {
                $tag = $this->_model->getTag($idtag);

                if (count($tag)) {
                    $data = array();
                    $form = $app['form.factory']->createBuilder('form', $data)
                        ->add(
                            'idpost', 'hidden', array(
                                'data' => $idpost,
                            )
                        )
                        ->add(
                            'idtag', 'hidden', array(
                                'data' => $idtag,
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
                                $model = $this->_model
                                    ->disconnectWithPost($data);

                                $app['session']->getFlashBag()->add(
                                    'message', array(
                                        'type' => 'success',
                                        'content' => 'Tag został usunięty'
                                    )
                                );
                                return $app->redirect(
                                    $app['url_generator']->generate(
                                        '/posts/'
                                    ), 301
                                );
                            } catch (\Exception $e) {
                                $errors[] = 'Coś poszło niezgodnie z planem';
                                return $app->redirect(
                                    $app['url_generator']->generate(
                                        '/posts/'
                                    ), 301
                                );

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
                        'tags/delete.twig', array(
                            'form' => $form->createView()
                        )
                    );

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'danger',
                            'content' => 'Nie znaleziono tagu'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/posts/'
                        ), 301
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono tagu'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono postu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Tags manager for post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function manageTag(Application $app, Request $request)
    {
        $id = (int)$request->get('idpost', 0);
        $check = $this->_posts->checkPostId($id);

        if ($check) {
            $tags = $this->_model->getTagsListByPost($id);
            return $app['twig']->render(
                'tags/manage.twig', array(
                    'tags' => $tags, 
                    'idpost' => $id
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono tagu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Tags control panel
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Geberates page.
     */
    public function controlTag(Application $app, Request $request)
    {
        $tags = $this->_model->getTagList();
        return $app['twig']->render(
            'tags/control.twig', array(
                'tags' => $tags
            )
        );
    }
}
