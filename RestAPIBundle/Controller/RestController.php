<?php

namespace CoffeeStudio\RestAPIBundle\Controller;

use CoffeeStudio\RestAPIBundle\Entity\RootAccessor;
use CoffeeStudio\RestAPIBundle\Handle\IRestHandle;
use CoffeeStudio\RestAPIBundle\Handle\Result;
use CoffeeStudio\RestAPIBundle\Util\IRestUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

class RestController extends Controller
{
    const HARNESS_NS = 'CoffeeStudio\\Harness\\';
    const SKEY = 'coffee.api.accessor';

    private static function findInterface($classn, $method, $dao = false)
    {
        $ifaces = class_implements($classn);
        foreach ($ifaces as $ifc) {
            if ( ($dao && ! is_subclass_of($ifc, self::HARNESS_NS . 'IDAO')) || strpos($ifc, self::HARNESS_NS) !== 0 ) {
                continue;
            }
            $cn = 'CoffeeStudio\\RestAPIBundle\\Handle\\ByInterface\\' . substr($ifc, strlen(self::HARNESS_NS));
            if (method_exists($cn, $method)) return $cn;
        }
        return null;
    }

    private function makeModelProcedure($name, $method, $accessor=null, $projection=null)
    {
        $em = $this->getDoctrine()->getManager();
        $ecn = $em->getClassMetadata($name)->getName();
        $dao = $em->getRepository($name);
        $pm = $projection ? $projection . 'Projection' : null;
        $projMap = $pm && method_exists($dao, $pm) ? $dao->$pm() : null;

        $hdl_cn = self::findInterface($dao, $method, true);
        if (! $hdl_cn) $this->e404(100);
        $hdl = null;
        try {
            $hdl = new $hdl_cn($dao, $ecn, $em, $projMap);
        } catch (\Exception $e) {
            $this->e404(200);
        }
        if (! $hdl instanceof IRestHandle) $this->e404(300);
        $procedure = $hdl->$method($accessor);
        $procedure->bindTo($hdl, $hdl);
        return $procedure;
    }

    private function makeUtilProcedure($name, $method, $accessor=null)
    {
        $util_cn = 'CoffeeStudio\\RestAPIBundle\\Util\\' . $name;
        $util = null;
        try {
            $util = new $util_cn;
        } catch (\Exception $e) {
            $this->e404();
        }
        if (! $util instanceof IRestUtil) $this->e404();
        $procedure = $util->$method($accessor);
        $procedure->bindTo($util, $util);
        return $procedure;
    }

    private function callProcedure($procedure, $options, $dataIn=null)
    {
        if (! is_callable($procedure)) $this->e403(100);
        $refl = new \ReflectionFunction($procedure);
        $args = [];
        foreach ($refl->getParameters() as $p) {
            $n = $p->getName();
            if ($n == 'dataIn') {
                $args[] = $dataIn;
            } elseif (is_array($options) && isset($options[$n])) {
                $args[] = $options[$n];
            } elseif ($options && $options->has($n)) {
                $args[] = $options->get($n);
            } else {
                $args[] = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
            }
        }
        try {
            return call_user_func_array($procedure, $args);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function makeResponse($restResult, $fieldset = '*')
    {
        $jsonOutArr = [];
        if ($restResult instanceof \Exception) {
            $jsonOutArr = ['type' => 'error', 'message' => json_encode($restResult->getMessage())];
        } elseif ($restResult instanceof Result) {
            $fieldset = $fieldset == '*' ? null : explode(',', $fieldset);
            $types = $restResult->getTypes();
            $jsonOutArr = ['type' => 'model', 'model' => $restResult($fieldset)];
            if (! empty($types)) {
                $jsonOutArr['types'] = $types;
            }
        } elseif (is_null($restResult)) {
            $jsonOutArr = ['type' => 'void'];
        } else {
            $jsonOutArr = ['type' => 'value', 'value' => $restResult];
        }
        return new JsonResponse($jsonOutArr);
    }

    private function mayBeProjection($fss)
    {
        if (preg_match('/@(\w+)/', $fss, $m)) {
            return $m[1];
        }
        return null;
    }

    private static function getDataIn($req)
    {
        $dataIn = [];
        if (strpos($req->headers->get('Content-Type'), 'application/json') === 0) {
            $dataIn = json_decode($req->getContent(), true);
        } else {
            $dataIn = $req->request->all();
        }
        return $dataIn;
    }

    public function modelGetAction($name, $method, $fieldset = '*', Request $req)
    {
        $proj = $this->mayBeProjection($fieldset);
        if ($proj) $fieldset = '*';
        $accessor = $this->getFirstAccessor($req);
        $p = $this->makeModelProcedure($name, $method, $accessor, $proj);
        $options = $req->query;
        return $this->makeResponse($this->callProcedure($p, $options), $fieldset);
    }

    public function modelUpdateAction($name, $method, $fieldset = '*', Request $req)
    {
        $proj = $this->mayBeProjection($fieldset);
        if ($proj) $fieldset = '*';
        $accessor = $this->getFirstAccessor($req);
        $p = $this->makeModelProcedure($name, $method, $accessor, $proj);
        $options = $req->query;
        $dataIn = self::getDataIn($req);
        return $this->makeResponse($this->callProcedure($p, $options, $dataIn), $fieldset);
    }

    /* FIXME: Should use named accessors */
    private function getFirstAccessor(Request $req)
    {
        $session = $req->getSession();
        $accessors = $session->get(self::SKEY);
        if (empty($accessors)) return null;
        list ($name, $userId) = each($accessors);
        $p = $this->makeModelProcedure($name, 'getUser', new RootAccessor);
        $result = $this->callProcedure($p, ['id' => $userId]);
        if (! $result instanceof Result) return null;
        /* TODO: Return entity */
        return $result;
    }

    public function getFeaturesAction($name)
    {
        $em = $this->getDoctrine()->getManager();
        $ecn = $em->getClassMetadata($name)->getName();
        $dao = $em->getRepository($name);
        $eimpl = array_values(array_filter(array_map(
            [$this, 'matchHarnessNS'],
            array_keys(class_implements($ecn, false))
        )));
        $dimpl = array_values(array_filter(array_map(
            [$this, 'matchHarnessNS'],
            array_keys(class_implements($dao, false))
        )));
        return new JsonResponse(['entity' => $eimpl, 'dao' => $dimpl]);
    }
    private function matchHarnessNS($cn)
    {
        $find = 'CoffeeStudio\\Harness\\';
        $len = 21;
        if (strpos($cn, $find) === 0) {
            return substr($cn, $len);
        } else {
            return null;
        }
    }

    public function authAction($name, $method, Request $req)
    {
        $methods = ['login', 'logout', 'check'];
        if (! in_array($method, $methods)) $this->e404();
        $session = $req->getSession();
        $accessor = $this->getFirstAccessor($req);
        $astorage = $session->get(self::SKEY);

        if ($method == 'check') {
            if (empty($astorage)) return $this->makeResponse(null);
            $userId = isset($astorage[$name]) ? $astorage[$name] : null;
            if (empty($userId)) return $this->makeResponse(null);
            $p = $this->makeModelProcedure($name, 'getUser', $accessor);
            return $this->makeResponse($this->callProcedure($p, ['id' => $userId]));
        }

        if ($method == 'logout') {
            if (empty($astorage)) return $this->makeResponse(null);
            unset($astorage[$name]);
            $session->set(self::SKEY, $astorage);
            return $this->makeResponse(true);
        }

        $p = $this->makeModelProcedure($name, $method, $accessor);
        $options = $req->query;
        $dataIn = self::getDataIn($req);
        $result = $this->callProcedure($p, $options, $dataIn);

        if ($method == 'login' && $result instanceof Result) {
            $as = $result();
            if (! empty($as)) {
                $userId = $as[0]['id'];
                if (empty($astorage)) $astorage = [];
                $astorage[$name] = $userId;
                $session->set(self::SKEY, $astorage);
            }
        }

        return $this->makeResponse($result);
    }

    public function utilGetAction($name, $method, Request $req)
    {
        $accessor = $this->getFirstAccessor($req);
        $p = $this->makeUtilProcedure($name, $method, $accessor);
        $options = $req->query;
        return $this->makeResponse($this->callProcedure($p, $options));
    }

    public function utilDoAction($name, $method, Request $req)
    {
        $accessor = $this->getFirstAccessor($req);
        $p = $this->makeUtilProcedure($name, $method, $accessor);
        $options = $req->query;
        $dataIn = self::getDataIn($req);
        return $this->makeResponse($this->callProcedure($p, $options, $dataIn));
    }

    private function filterConfig(array $data, $section = '*', $subsection = '*', $param = '*')
    {
        $section == '*' && $section = null;
        $subsection == '*' && $subsection = null;
        $param == '*' && $param = null;
        foreach ([$section, $subsection, $param] as $reducer) {
            if (! $reducer) break;
            if (! isset($data[$reducer])) $this->e404($reducer);
            $data = $data[$reducer];
        }
        return $data;
    }

    public function configGetAction($section = '*', $subsection = '*', $param = '*', Request $req)
    {
        $config_file = $this->get('kernel')->getRootDir() . '/config/coffeestudio.yml';
        $yaml = new Parser;
        $data = $yaml->parse(file_get_contents($config_file));
        return new JsonResponse($this->filterConfig($data, $section, $subsection, $param));
    }

    public function configSetAction($section = '*', $subsection = '*', $param = '*', Request $req)
    {
        $dataIn = self::getDataIn($req);
        return new Response('Not implemented.');
    }

    public function langGetAction($section = '*', $subsection = '*', $param = '*', Request $req)
    {
        $config_file = $this->get('kernel')->getRootDir() . '/config/lang.ru.yml';
        $yaml = new Parser;
        $data = $yaml->parse(file_get_contents($config_file));
        return new JsonResponse($this->filterConfig($data, $section, $subsection, $param));
    }

    public function langSetAction($section = '*', $subsection = '*', $param = '*', Request $req)
    {
        $dataIn = self::getDataIn($req);
        return new Response('Not implemented.');
    }

    private function e404($marker=null)
    {
        $marker = $marker ? ', "marker": "'.$marker.'"' : '';
        throw $this->createNotFoundException('{"type": "error", "message": "Requested API method not found."'.$marker.'}');
    }
    private function e403($marker=null)
    {
        $marker = $marker ? ', "marker": "'.$marker.'"' : '';
        throw $this->createAccessDeniedException('{"type": "error", "message": "You have no access to this API method."'.$marker.'}');
    }
}
