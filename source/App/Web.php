<?php

namespace Source\App;

use Source\Core\Connect;
use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\Category;
use Source\Models\Faq\Channel;
use Source\Models\Faq\Question;
use Source\Models\Post;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Email;
use Source\Support\Pager;

class Web extends Controller
{
    /**
     * Method __construct
     *
     * @return void
     */
    public function __construct()
    {

        //redirect("/ops/manutencao");
        //Connect::getInstance();
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");
        
        (new Access())->report();
        (new Online())->report();

        
    }

    /**
     * Method home
     *
     * @return void
     */
    public function home(): void
    {

        $head = $this->seo->render(
            CONF_SITE_NAME . " - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url(),
            url("/assets/images/share.jpg")
        );
        echo $this->view->render("home", [
            "head" => $head,
            "video" => "g29xXRHtnIs",
            "blog" => (new Post())->find()->order("post_at DESC")->limit(6)->fetch(true)
        ]);
    }

    public function about(): void
    {

        $head = $this->seo->render(
            CONF_SITE_NAME . " - " . CONF_SITE_DESC,
            CONF_SITE_DESC,
            url("/sobre"),
            url("/assets/images/share.jpg")
        );
        echo $this->view->render("about", [
            "head" => $head,
            "video" => "g29xXRHtnIs",
            "faq" => (new Question())->find("channel_id = :id", "id=1", "question, response")
                ->order("order_by")
                ->fetch(true)
        ]);
    }


    public function blog(?array $data): void
    {
        $head = $this->seo->render(
            " Blog - " . CONF_SITE_NAME,
            "Confira em nosso blog dicas e sacadas de como controlar melhorar suas contas. Vamos tomar um cafe?",
            url("/blog"),
            theme("/assets/images/share.jpg")
        );

        $blog = (new Post())->find();
        $pager = new Pager(url("/blog/p/"));
        $pager->pager($blog->count(), 9, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            "head" => $head,
            "blog" => $blog->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }
    
    /**
     * Method blogcategory
     * @param array $data [explicite description]
     * @return void
     */
    public function blogCategory(array $data): void
    {
        $catecoryUri = filter_var($data["category"], FILTER_SANITIZE_SPECIAL_CHARS);
        $category = (new Category())->findByUri($catecoryUri);

        if(!$category){
            redirect("/blog");
        }

        $blogCategory = (new Post())->find("category = :c", "c={$category->id}");
        $page = (!empty($data['page']) && filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);
        $pager = new Pager(url("/blog/em/{$category->uri}/"));
        $pager->pager($blogCategory->count(), 9, $page);

        $head = $this->seo->render(
            "Artigos em {$category->title} - " . CONF_SITE_NAME,
            $category->description,
            url("/blog/em/{$category->uri}/{$page}"),
            ($category->cover ? image($category->cover, 1200, 628) : theme("/assets/images/share.jpg"))
        );

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Artigos em {$category->title}",
            "desc" => $category->description,
            "blog" => $blogCategory->limit($pager->limit())->offset($pager->offset())->order("post_at DESC")->fetch(true),
            "paginator" => $pager->render()
        ]);

    }

    public function blogSearch(array $data) : void
    {
        if(!empty($data['s'])){
            $search = filter_var($data['s'], FILTER_SANITIZE_SPECIAL_CHARS);
            echo json_encode(["redirect" => url("/blog/buscar/{$search}/1")]);
            return;
        }
        if(empty($data['terms'])){
            redirect("/blog");
        }
        $search = filter_var($data['terms'], FILTER_SANITIZE_SPECIAL_CHARS);
        $page = (filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);

        $head = $this->seo->render(
            "Pesquyisa por {$search} - ". CONF_SITE_NAME,
            "Confira os resultados de sua pesquisa para {$search}",
            url("/blog/buscar/{$search}/{$page}"),
            theme("/assets/images/share.jpg")
        );
        $blogSearch= (new Post())->find("MATCH(title, subtitle) AGAINST(:s)", "s={$search}");
        
        if(!$blogSearch->count()){
            echo $this->view->render("blog",[
                "head" => $head,
                "title" => "PESQUISA POR:",
                "search" => $search
            ]);
            return;
        }

        $pager = new Pager(url("/blog/buscar/{$search}/"));
        $pager->pager($blogSearch->count(), 9, $page);

        echo $this->view->render("blog", [
            "head" => $head,
                "title" => "PESQUISA POR:",
                "search" => $search,
                "blog" => $blogSearch->limit($pager->limit())->offset($pager->offset())->fetch(true),
                "paginator" => $pager->render()
        ]);
    }

    public function blogPost(?array $data): void
    {

        // Busca o post pelo campo "uri" ou equivalente
        $post = (new Post())->findByUri($data['uri']);
        // Se não encontrar o post, redireciona ou mostra 404
        if (!$post) {
            redirect("/404");
            return;
        }

        $head = $this->seo->render(
            $post->title . " - " . CONF_SITE_NAME,
            $post->subtitle,
            url("/blog/{$post->uri}"),
            image($post->cover, 1200, 628)
        );

        echo $this->view->render("blog-post", [
            "head" => $head,
            "post" => $post,
            "related" => (new Post())->find("category = :c AND id != :i", "c={$post->category}&i={$post->id}")
                ->order("rand()")
                ->limit(3)
                ->fetch(true)
        ]);
    }

    
    /**
     * Method login
     *
     * @param ?array $data [explicite description]
     *
     * @return void
     */
    public function login(?array $data): void
    {
        if(Auth::user()){
            redirect("/app");
        }

        if(!empty($data['csrf'])){
            if(!csrf_verify($data)){
                $json["message"] = $this->message->error("Erro ao enviar, favor use o formulario")->render();
                echo json_encode($json);
                return;
            }
            if(request_limit("weblogintest", 3, "5")){
                 $json["message"] = $this->message->error("Voce ja efetuou suas 3 tentativas por favor aguarde 5 minutos para tentar novamente.")->render();
                echo json_encode($json);
                return;
            }
            if(empty($data['email']) || empty($data['password'])){
                $json['message'] = $this->message->warning("Informe seu email e senha para entrar")->render();
                echo json_encode($json);
                return;
            }
            $save = (!empty($data['save']) ? true : false);
            $auth = new Auth();
            $login = $auth->login($data['email'], $data['password'], $save);

            if($login){
                $this->message->success("Seja bem-vindo(a) de volta ".Auth::user()->first_name . "!")->flash();
                $json['redirect'] = url("/app");
            }else{
                $json['message'] = $auth->message()->before("Ooops! ")->render();
            }


            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            " ENTRAR - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/entrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-login", [
            "head" => $head,
            "cookie" => filter_input(INPUT_COOKIE, "authEmail")
        ]);
    }

    public function forget(?array $data)
    {
        if(Auth::user()){
            redirect("/app");
        }

        if(!empty($data['csrf'])){
            if(!csrf_verify($data)){
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulario")->render();
                echo json_encode($json);
                return;
            }
            if(empty($data["email"])){
                $json['message'] = $this->message->info("Informe seu email para continuar")->render();
                echo json_encode($json);
                return;
            }

            if(request_repeat("webforget", $data["email"])){
                $json['message'] = $this->message->error("Ooops, Voce ja tentou esse e-mail antes")->render();
                echo json_encode($json);
                return;
            }
            $auth = new Auth();
            if($auth->forget($data["email"])){
                $json["message"] = $this->message->success("Acesse seu email para recuperar sua senha.")->render();
            }else{
                $json["message"] = $auth->message()->before("Ooops! ")->render();
            }
            echo json_encode($data);
            return;
        }

        $head = $this->seo->render(
            " Recuperar Senha - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-forget", [
            "head" => $head
        ]);
    }


    public function reset(?array $data): void
    {
        if(Auth::user()){
            redirect("/app");
        }
        
        if(!empty($data['csrf'])){
            if(!csrf_verify($data)){
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulario")->render();
                echo json_encode($json);
                return;
            }
            if(empty($data["password"]) || empty($data["password_re"])){
                $json["message"] = $this->message->info("Informe e repita a senha para continuar")->render();
                echo json_encode($json);
                return;
            }
            list($email, $code) = explode("|", $data["code"]);
            $auth = new Auth();

            if($auth->reset($email, $code, $data["password"], $data["password_re"])){
                $this->message->success("Senha Alterada com sucesso vamos controlar?.")->flash();
                $json["redirect"] = url("/entrar");
            }else{
                $json["message"] = $auth->message()->before("Ooops! ")->render();
            }
            echo json_encode($json);
            return;
        }
        $head = $this->seo->render(
            "Crie sua nova senha no " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("auth-reset", [
            "head" => $head,
            "code" => $data["code"]
        ]);
    }
    public function register(?array $data): void
    {

        if(!empty($data['csrf'])){
            if(!csrf_verify($data)){
                $json["message"] = $this->message->error("Erro ao enviar, favor use o formulario")->render();
                echo json_encode($json);
                return;
            }

            if(in_array("", $data)){
                $json["message"] = $this->message->info("Informe seus dados para criar sua conta.")->render();
                echo json_encode($json);
                return;
            }
            $auth = new Auth();
            $user =new User();
            $user->bootstrap(
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['password']
            );

            if($auth->register($user)){
                $json['redirect'] = url("/confirma");
            }else{
                $json['message'] = $auth->message()->before("Ooops! ")->render();
            }
            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Criar Conta - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/cadastrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-register", [
            "head" => $head
        ]);
    }


    public function confirm()
    {
        $head = $this->seo->render(
            "Confirme Seu Cadastro - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/confirma"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("optin", [
            "head" => $head,
            "data" =>(object)[
                "title" => "Falta pouco! Confirme seu cadastro.",
                "desc" => "Enviamos um link de confirmação para seu e-mail. Acesse e siga as instruções para concluir seu cadastro e comece a controlar com o CaféControl",
                "image" => theme("/assets/images/optin-confirm.jpg")
            ]
            
        ]);
    }
    
    /**
     * @param array $data
     */
    public function success(array $data): void
    {
        $email = base64_decode($data["email"]);
        $user = (new User())->findByEmail($email);

        if($user && $user->status != "confirmed"){
            $user->status = "confirmed";
            $user->save();
        }

        $head = $this->seo->render(
            "Bem-vindo(a) ao " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/obrigado"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("optin", [
            "head" => $head,
            "data" =>(object)[
                "title" => "Falta pouco! Confirme seu cadastro.",
                "desc" => "Enviamos um link de confirmação para seu e-mail. Acesse e siga as instruções para concluir seu cadastro e comece a controlar com o CaféControl",
                "image" => theme("/assets/images/optin-confirm.jpg"),
                "link" => url("/entrar"),
                "linkTitle" => "Fazer Login"
            ]
        ]);
    }

    public function terms(): void
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - Termso de uso",
            CONF_SITE_DESC,
            url("/termos"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("terms", [
            "head" => $head,
            "video" => "g29xXRHtnIs"
        ]);
    }

    /**
     * Method error
     *
     * @param array $data [explicite description]
     *
     * @return void
     */
    public function error(array $data): void
    {
        $error = new \stdClass();

        switch ($data['errcode']) {
            case "error":
                $error->code = "Ops";
                $error->title = "Estamos enfretando Problemas :/";
                $error->message = "Parece que noss servico não está disponivel no momento. Já estamos trabalhando nisso caso precise envie um e-mail para guilhermemendes.info2gmail.com.";
                $error->linkTitle = "ENVIAR E-MAIL";
                $error->link = null;
                break;
            case "manutencao":
                $error->code = "Ops";
                $error->title = "Estamos em Manutencao :/";
                $error->message = "Sentimos muito, mas o conteudo que voce tentou acessar não existe, está indisponivel no momento.";
                $error->linkTitle = null;
                $error->link = null;
                break;
            default:
                $error->code = $data['errcode'];
                $error->title = "Ooops, Conteudo indisponivel :/";
                $error->message = "Sentimos muito, mas o conteudo que voce tentou acessar não existe, está indisponivel no momento.";
                $error->linkTitle = "Continue navegando";
                $error->link = url_back();
                break;
        }



        $head = $this->seo->render(
            "{$error->code} | {$error->title}",
            $error->message,
            url("/ops/{$error->code}"),
            theme("/assets/images/share.jpg"),
            false
        );
        echo $this->view->render("error", [
            "head" => $head,
            "error" => $error
        ]);
    }
}
