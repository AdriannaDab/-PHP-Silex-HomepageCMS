<?php
 /**
 * Blog posts controller 
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
use Model\PostsModel;
use Model\TagsModel;
use Model\CategoriesModel;
use Model\UsersModel;
 
/**
 * Class PostsController
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
 * @uses Model\PostsModel
 * @uses Model\TagsModel
 * @uses Model\CategoriesModel
 * @uses Model\UsersModel
 */
class PostsController implements ControllerProviderInterface
{
    /**
     * PostsModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * TagsModel object.
     *
     * @var $_tags
     * @access protected
     */
    protected $_tags;

    /**
     * CategoryiesModel object.
     *
     * @var $_category
     * @access protected
     */
    protected $_category;

    /**
     * UsersModel object.
     *
     * @var $_user
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
        $this->_model = new PostsModel($app);
        $this->_tags = new TagsModel($app);
        $this->_category = new CategoriesModel($app);
        $this->_user = new UsersModel($app);
        $postController = $app['controllers_factory'];
        $postController->get('/{page}', array($this, 'index'))
            ->value('page', 1)
            ->bind('/posts/');
        $postController->match('/add/', array($this, 'add'))
            ->bind('/posts/add');
        $postController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/posts/edit');
        $postController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/posts/delete');
        $postController->get('/view/{id}', array($this, 'view'))
            ->bind('/posts/view');
        return $postController;
    }

    /**
     * View all posts
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
        $pagesCount = $this->_model->countPostsPages($pageLimit);
        if (($page < 1) || ($page > $pagesCount)) {
            $page = 1;
        }
        $posts = $this->_model->getPostsPage($page, $pageLimit, $pagesCount);
        $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
        return $app['twig']->render(
            'posts/index.twig', array(
                'posts' => $posts, 
                'paginator' => $paginator
            )
        );
    }

    /**
     * Add new post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or Redirect.
     */
    public function add(Application $app, Request $request)
    {

        $categories = $this->_category->getCategoriesDict();

        $data = array(
            'published_date' => date('Y-m-d'),
        );

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
                            'message' => 'Tytuł nie jest oprawny.',
                        )
                    )
                )
            )
            )
            ->add(
                'content', 'textarea', array(
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
                                    'message' => 'Tekst nie jest poprawny.',
                                )
                            )
                )
            )
            )
            ->add(
                'published_date', 'date', array(
                    'input' => 'string',
                    'widget' => 'single_text',
                    'constraints' => array(
                        new Assert\Date()
                    )
                )
            )
            ->add(
                'category', 'choice', array(
                    'choices' => $categories,
                    )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            try {
                $model = $this->_model->addPost($data);

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Post został dodany'
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
            'posts/add.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Edit post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or Redirect.
     */
    public function edit(Application $app, Request $request)
    {
        $categories = $this->_category->getCategoriesDict();

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkPostId($id);

        if ($check) {

            $post = $this->_model->getPost($id);

            $data = array(
                'title' => $post['title'],
                'content' => $post['content'],
                'published_date' => $post['published_date'],
                'idcategory' => $post['idcategory'],
            );

            if (count($post)) {
                $form = $app['form.factory']->createBuilder('form', $data)
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
                        'content', 'textarea', array(
                        'required' => false), array(
                        'constraints' => array(new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                 'min' => 5,
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
                    ->add(
                        'pulished_date', 'date', array(
                            'input' => 'string',
                            'widget' => 'choice',
                            'constraints' => array(
                                new Assert\Date()
                            )
                        )
                    )
                    ->add(
                        'category', 'choice', array(
                            'choices' => $categories,
                        )
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $model = $this->_model->editPost($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Post został zmieniony'
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
                    'posts/edit.twig', array(
                        'form' => $form->createView(), 
                        'idpost' => $id
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleniono postu'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                     'type' => 'danger',
                     'content' => 'Nie znaleniono postu'
                 )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/add'
                ), 301
            );
        }
    }

    /**
     * Delete post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or Redirect.
     */
    public function delete(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkPostId($id);

        if ($check) {

            $post = $this->_model->getPost($id);

            $data = array();

            if (count($post)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idpost', 'hidden', array(
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
                            $model = $this->_model->deletePost($data['idpost']);

                            $app['session']->getFlashBag()
                                ->add(
                                    'message', array(
                                        'type' => 'success',
                                        'content' => 'Post został usunięty'
                                    )
                                );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/'
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
                    'posts/delete.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                         'type' => 'danger',
                         'content' => 'Nie znaleniono postu'
                     )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleniono postu'
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
     * Generate one post view
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or Redirect.
     */
    public function view(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkPostId($id);

        if ($check) {
            $post = $this->_model->getPostWithCategoryName($id);
            $tags = $this->_tags->getTagsListByPost($id);

            if (count($post)) {
                return $app['twig']->render(
                    'posts/view.twig', array(
                        'post' => $post, 
                        'tags' => $tags
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleniono postu'
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
                    'content' => 'Nie znaleniono postu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }
}
