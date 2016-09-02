<?php
/**
 * Bach matricules controller
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Anaphore
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3)The name of the author may not be used to
 *    endorse or promote products derived from this software without
 *    specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Search
 * @package  Bach
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Bach\HomeBundle\Form\Type\MatriculesType;
use Bach\HomeBundle\Entity\SolariumQueryContainer;
use Bach\HomeBundle\Entity\Filters;
use Bach\HomeBundle\Entity\Facets;
use Bach\HomeBundle\Form\Type\SearchQueryFormType;
use Bach\HomeBundle\Entity\SearchQuery;
use Bach\HomeBundle\Entity\MatriculesViewParams;
use Bach\HomeBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Bach federate controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class FederateController extends Controller
{

    /**
     * Search federate page
     *
     * @param string $query_terms Term(s) we search for
     * @param int    $page        Page ead
     * @param int    $page_mat    Page matricules
     * @param string $facet_name  Display more terms in suggests
     * @param string $form_name   Search form name
     *
     * @return void
     */
    public function searchAction($query_terms = null, $page = 1, $page_mat = 1,
        $facet_name = null, $form_name = null
    ) {
        if ($page == 0) {
            $page = 1;
        }
        if ($page_mat == 0) {
            $page_mat = 1;
        }
        if ($this->getRequest()->get('clear_filters')) {
            $facet_name = null;
            $this->getRequest()->getSession()->set('clear_filters_ead', true);
            $this->getRequest()->getSession()->set('clear_filters_mat', true);
        }
        if ($query_terms != null) {
            $queryEad = $query_terms;
            $queryMat = $query_terms;
        } else {
            $queryMat = $queryEad = '*:*';
        }
        if ($this->getRequest()->get('form_name') == 'default') {
            $this->forward(
                'BachHomeBundle:Default:search',
                array(
                    'form_name'   => $form_name,
                    'query_terms' => $query_terms,
                    'page'        => $page,
                    'page_mat'    => $page_mat,
                    'facet_name'  => $facet_name,
                    'request'     => $this->getRequest()
                )
            );

            if ($this->getRequest()->get('from_doc_view') == null ) {
                $this->forward(
                    'BachHomeBundle:Matricules:search',
                    array(
                        'form_name'   => 'matricules',
                        'query_terms' => $queryMat,
                        'page'        => $page_mat,
                        'facet_name'  => $facet_name
                    )
                );
            }
        } elseif ($this->getRequest()->get('form_name') == 'matricules') {
            $this->forward(
                'BachHomeBundle:Default:search',
                array(
                    'form_name'   => $form_name,
                    'query_terms' => $queryEad,
                    'page'        => $page,
                    'page_mat'    => $page_mat,
                    'facet_name'  => $facet_name
                )
            );
            if ($this->getRequest()->get('from_doc_view') == null ) {
                $this->forward(
                    'BachHomeBundle:Matricules:search',
                    array(
                        'form_name'   => 'matricules',
                        'query_terms' => $query_terms,
                        'page'        => $page_mat,
                        'facet_name'  => $facet_name,
                        'request'     => $this->getRequest()
                    )
                );
            }
        } else {
            $this->forward(
                'BachHomeBundle:Default:search',
                array(
                    'form_name'   => $form_name,
                    'query_terms' => $query_terms,
                    'page'        => $page,
                    'page_mat'    => $page_mat,
                    'facet_name'  => $facet_name
                )
            );
            if ($this->getRequest()->get('from_doc_view') == null ) {
                $this->forward(
                    'BachHomeBundle:Matricules:search',
                    array(
                        'form_name'   => 'matricules',
                        'query_terms' => $query_terms,
                        'page'        => $page_mat,
                        'facet_name'  => $facet_name
                    )
                );
            }

        }
        $eadTplVars = (array)$this->getRequest()->getSession()->get('searchEad');
        $matriculesTplVars = $this->getRequest()->getSession()->get('searchMat');
        $matriculesTplVars['search_path'] = 'bach_matricules';
        $matriculesTplVars['pageEad'] = (int)$eadTplVars['page'];
        $eadTplVars['page'] = (int)$eadTplVars['page'];
        $eadTplVars['from_doc_view'] = $this->getRequest()->get('from_doc_view');
        if ($this->getRequest()->get('form_name') == 'matricules') {
            $eadTplVars['form'] = $matriculesTplVars['form'];
        }
        $eadTplVars['matriculesSearch'] = $matriculesTplVars;
        return $this->render(
            'BachHomeBundle:Default:index.html.twig',
            $eadTplVars
        );
    }

    /**
     * Suggests
     *
     * @return void
     */
    public function doSuggestAction()
    {
        $suggestEad = $this->forward(
            'BachHomeBundle:Default:doSuggest',
            array('q' => $this->getRequest()->get('q'))
        );
        $suggestMat = $this->forward(
            'BachHomeBundle:Matricules:doSuggest',
            array('q' => $this->getRequest()->get('q'))
        );
        $suggest = array_merge(
            json_decode($suggestEad->getContent(), true),
            json_decode($suggestMat->getContent(), true)
        );
        return new JsonResponse(array_unique($suggest));
    }
}
