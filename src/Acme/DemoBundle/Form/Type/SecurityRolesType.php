<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Acme\DemoBundle\Form\Type;

use Acme\DemoBundle\Form\Transformer\RestoreRolesTransformer;
use Acme\DemoBundle\Security\EditableRolesBuilder;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

class SecurityRolesType extends AbstractType
{
    protected $rolesBuilder;
    protected $pool;

    /**
     * @param EditableRolesBuilder $rolesBuilder
     */
    public function __construct(EditableRolesBuilder $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
        $this->pool = $rolesBuilder->getPool();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {

        /**
         * The form shows only roles that the current user can edit for the targeted user. Now we still need to persist
         * all other roles. It is not possible to alter those values inside an event listener as the selected
         * key will be validated. So we use a Transformer to alter the value and an listener to catch the original values
         *
         * The transformer will then append non editable roles to the user ...
         */
        $transformer = new RestoreRolesTransformer($this->rolesBuilder);

        // GET METHOD
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($transformer) {
            $transformer->setOriginalRoles($event->getData());
        });

        // POST METHOD
        $formBuilder->addEventListener(FormEvents::PRE_BIND, function (FormEvent $event) use ($transformer) {
            $transformer->setOriginalRoles($event->getForm()->getData());
        });

        $formBuilder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $attr = $view->vars['attr'];
        if (isset($attr['class']) && empty($attr['class'])) {
            $attr['class'] = 'sonata-medium';
        }
        $view->vars['hierarchicalRoles'] = $options['hierarchicalRoles'];
        $view->vars['attr'] = $attr;
        $view->vars['read_only_choices'] = $options['read_only_choices'];
        $selectedRoles = $form->getData();
        if (!empty($selectedRoles)) {
            $view->vars['selected_choices'] = $selectedRoles;
        } else {
            $view->vars['selected_choices'] = '';
        }
    }

    public function cleanTitle($string)
    {
        $string = str_replace(array('(', ')'), '', $string);
        $string = str_replace('sonata_', '', $string);
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        return $string;
    }

    public function getAdminNameFromServiceId($service)
    {
        $explode = explode('.', $service);
        return '_' . end($explode) . '_';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        list($roles, $rolesReadOnly) = $this->rolesBuilder->getRoles();
        /**
         * Here Get all Admin services
         */
        $groups = $this->pool->getDashboardGroups();
        $i = 0;
        foreach ($this->pool->getAdminServiceIds() as $key => $val) {
            $getAdminNameFromServiceId = $this->getAdminNameFromServiceId($val);
            $ModuleRoles[$i]['label'] = $this->cleanTitle($getAdminNameFromServiceId);
            $ModuleRoles[$i]['id'] = $val;
            foreach ($roles as $r) {

                if (strpos($r, strtoupper($getAdminNameFromServiceId)) !== false) {
                    if (strpos($r, '_CREATE') !== false) {
                        $ModuleRoles[$i]['permissions']['CREATE'] = $r;
                    }
                    if (strpos($r, '_EDIT') !== false) {
                        $ModuleRoles[$i]['permissions']['EDIT'] = $r;
                    }
                    if (strpos($r, '_LIST') !== false) {
                        $ModuleRoles[$i]['permissions']['LIST'] = $r;
                    }
                    if (strpos($r, '_VIEW') !== false) {
                        $ModuleRoles[$i]['permissions']['VIEW'] = $r;
                    }
                    if (strpos($r, '_DELETE') !== false) {
                        $ModuleRoles[$i]['permissions']['DELETE'] = $r;
                    }
                    if (strpos($r, '_EXPORT') !== false) {
                        $ModuleRoles[$i]['permissions']['EXPORT'] = $r;
                    }


                }
            }
            $i++;
        }

        $hierarchicalRoles = array();
        $index = 0;
        foreach ($groups as $key => $val) {
            foreach ($val['items'] as $key => $modules) {
                $hierarchicalRoles[$index]['Grouplabel'] = $this->cleanTitle($val['label']);
                foreach ($ModuleRoles as $key => $m) {
                    if ($modules->getCode() == $m['id']) {
                        $hierarchicalRoles[$index]['modules'][] = $m;
                    }
                }
            }
            $index++;
        }

        $resolver->setDefaults(array(
            'choices' => function (Options $options, $parentChoices) use ($roles) {
                return empty($parentChoices) ? $roles : array();
            },
            'hierarchicalRoles' => $hierarchicalRoles,
            'read_only_choices' => function (Options $options) use ($rolesReadOnly) {
                return empty($options['choices']) ? $rolesReadOnly : array();
            },

            'data_class' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sonata_security_roles';
    }
}