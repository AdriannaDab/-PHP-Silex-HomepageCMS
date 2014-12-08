<?php
 /**
 * Blog categories controller 
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
use Model\CategoriesModel;
use Model\PostsModel;
 
/**
 * Class CategoriesController
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
 * @uses Model\CategoriesModel
 */
class CategoriesController implements ControllerProviderInterface
{
    /**
     *
     * CategriesModel object
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     *
     * PostsModel object
     *
     * @var $_posts
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
        $this->_model = new CategoriesModel($app);
        $this->_posts = new PostsModel($app);
        $categoryController = $app['controllers_factory'];
        $categoryController->get('/{page}/{idcategory}/', array($this, 'index'))
            ->value('page', 1)->bind('/categories/');
        $categoryController->match('/add/', array($this, 'add'))
            ->bind('/categories/add');
        $categoryController->match('/edit/{idcategory}', array($this, 'edit'))
            ->bind('/categories/edit');
        $categoryController
            ->match('/delete/{idcategory}', array($this, 'delete'))
            ->bind('/categories/delete');
        $categoryController
            ->match('/controlpanel/', array($this, 'controlCategory'))
            ->bind('/categories/controlpanel');
        return $categoryController;
    }

    /**
     * View all posts from one category
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function index(Application $app, Request $request)
    {
        $id = (int)$request->get('idcategory', 0);

        $check = $this->_model->checkCategoryId($id);

        if ($check) {
            $post = $this->_model->getPostsListByIdcategory($id);
            return $app['twig']
                ->render('categories/index.twig', array('posts' => $post));
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono kategorii'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('/posts/'), 301
            );
        }
    }

    /**
     * Add new Category
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
                'name', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'max' => 45,
                            'minMessage' => 'Minimalna ilość znaków to 3',
                            'maxMessage' => 
                                'Maksymalna ilość znaków to {{ limit }}',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Nazwa nie jest poprawna',
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
                $model = $this->_model->addCategory($data);
                $app['session']->getFlashBag()
                ->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Kategoria została dodana'
                    )
                );
                return $app->redirect(
                    $app['url_generator']
                        ->generate('/categories/controlpanel'), 301
                );
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło niezgodnie z planem';
            }
        }

        return $app['twig']
            ->render(
                'categories/add.twig', array('form' => $form->createView())
            );
    }

    /**
     * Edit Category
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function edit(Application $app, Request $request)
    {

        $id = (int)$request->get('idcategory', 0);

        $check = $this->_model->checkCategoryId($id);

        if ($check) {
            $category = $this->_model->getCategory($id);

            $data = array(
                'name' => $category['name'],
                'idcategory' => $id
            );

            if (count($category)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'name', 'text', array(
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
                                    'message' => 'Nazwa nie jest zmieniona',
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
                        $model = $this->_model->editCategory($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Kategoria została zmieniona'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']
                                ->generate('/categories/controlpanel'), 301
                        );
                    } catch (\Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }

                return $app['twig']->render(
                    'categories/edit.twig', array('form' => $form->createView())
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono kategorii'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate('/categories/add'), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono kategorii'
                    )
            );
            return $app->redirect(
                $app['url_generator']->generate("/categories/controlpanel"), 301
            );
        }
    }

    /**
     * Delete category
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function delete(Application $app, Request $request)
    {
        $id = (int)$request->get('idcategory', 0);

        $check = $this->_model->checkCategoryId($id);

        if ($check) {

            $posts = $this->_model->getPostsListByIdcategory($id);

            if (!$posts) {
                $category = $this->_model->getCategory($id);

                $data = array();

                if (count($category)) {
                    $form = $app['form.factory']->createBuilder('form', $data)
                        ->add(
                            'idcategory', 'hidden', array(
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
                                $model = $this->_model->deleteCategory($data);

                                $app['session']->getFlashBag()->add(
                                    'message', array(
                                        'type' => 'success',
                                        'content' => 
                                            'Kategoria została usunięta'
                                    )
                                );
                                return $app->redirect(
                                    $app['url_generator']->generate(
                                        '/categories/controlpanel'
                                    ), 301
                                );
                            } catch (\Exception $e) {
                                $errors[] = 'Coś poszło niezgodnie z planem';
                            }
                        } else {
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/categories/controlpanel'
                                ), 301
                            );
                        }
                    }
                    return $app['twig']->render(
                        'categories/delete.twig', array(
                            'form' => $form->createView()
                        )
                    );
                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'danger',
                            'content' => 'Nie znaleziono kategorii'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/categories/controlpanel'
                        ), 301
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie można usunąć niepustej kategorii'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/categories/controlpanel'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono kategorii'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/categories/controlpanel'
                ), 301
            );
        }
    }

    /**
     * Connect category with post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page redirect.
     */
    public function connectCategory(Application $app, Request $request)
    {
        $idpost = (int)$request->get('idpost', 0);

        $check = $this->_posts->checkPostId($idpost);

        if ($check) {
            $categories = $this->_model->getCategorysDict();

            $data = array();

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'idcategory', 'choice', array(
                    'choices' => $categories,
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
                            'content' => 'Kategoria została dodana'
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
                'categories/connect.twig', array(
                    'form' => $form->createView()
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono kategorii'
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
     * Disconnected category with post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function disconnectCategory(Application $app, Request $request)
    {
        $idpost = (int)$request->get('idpost', 0);

        $checkPost = $this->_posts->checkPostId($idpost);

        if ($checkPost) {

            $idcategory = (int)$request->get('idcategory', 0);

            $checkCategory = $this->_model->checkCategoryId($idcategory);

            if ($checkCategory) {
                $category = $this->_model->getCategory($idcategory);

                if (count($category)) {
                    $data = array();
                    $form = $app['form.factory']->createBuilder('form', $data)
                        ->add(
                            'idpost', 'hidden', array(
                            'data' => $idpost,
                            )
                        )
                        ->add(
                            'idcategory', 'hidden', array(
                            'data' => $idcategory,
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
                                        'content' => 
                                            'Kategoria została usunięta'
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
                        'categories/delete.twig', array(
                                'form' => $form->createView()
                        )
                    );

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'danger',
                            'content' => 'Nie znaleziono kategorii'
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
                        'content' => 'Nie znaleziono kategorii'
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
     * Category control panel
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page.
     */
    public function controlCategory(Application $app, Request $request)
    {
        $categories = $this->_model->getCategories();
        return $app['twig']->render(
            'categories/control.twig', array(
                'categories' => $categories
            )
        );
    }
}
