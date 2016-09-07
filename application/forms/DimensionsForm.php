<?php

namespace Icinga\Module\Cube\Forms;

use Icinga\Module\Cube\Cube;
use Icinga\Module\Cube\Web\Form;

class DimensionsForm extends Form
{
    private $cube;

    public function setCube(Cube $cube)
    {
        $this->cube = $cube;
        return $this;
    }

    public function setup()
    {
        $cube = $this->cube;

        $dimensions = array_diff(
            $cube->listAdditionalDimensions(),
            $cube->listDimensions()
        );

        if (! empty($dimensions)) {
            $dimensions = array_combine($dimensions, $dimensions);
        }

        $this->addElement('select', 'addDimension', array(
            'multiOptions' => array(
                null => $this->translate('+ Add a dimension')
            ) + $dimensions,
            'decorators'   => array('ViewHelper'),
            'class'        => 'autosubmit'
        ));

        $dimensions = $cube->listDimensions();
        $cnt = count($dimensions);
        foreach ($dimensions as $pos => $dimension) {
            $this->addDimensionButtons($dimension, $pos, $cnt);
        }

        $this->setSubmitLabel(false);
    }

    protected function addDimensionButtons($dimension, $pos, $total)
    {
        $this->addHtml(
            '<span>' . $this->getView()->escape($dimension) . '</span>',
            array('name' => 'dimension_' . $dimension)
        );

        $this->addElement('submit', 'removeDimension_' . $dimension, array(
            'label' => $this->translate('x'),
            'decorators' => array('ViewHelper')
        ));

        $this->addElement('submit', 'moveDimensionUp_' . $dimension, array(
            'label' => sprintf($this->translate('^'), $dimension),
            'decorators' => array('ViewHelper'),
        ));

        $this->addElement('submit', 'moveDimensionDown_' . $dimension, array(
            'label' => sprintf($this->translate('^'), $dimension),
            'decorators' => array('ViewHelper')
        ));

        if ($pos === 0) {
            $this->getElement('moveDimensionUp_' . $dimension)->disabled = 'disabled';
        }

        if ($pos + 1 === $total) {
            $this->getElement('moveDimensionDown_' . $dimension)->disabled = 'disabled';
        }

        $this->addSimpleDisplayGroup(
            array(
                'dimension_' . $dimension,
                'removeDimension_' . $dimension,
                'moveDimensionUp_' . $dimension,
                'moveDimensionDown_' . $dimension,
            ),
            $dimension,
            array('class' => 'dimensions')
        );
    }

    public function onRequest()
    {
        parent::onRequest();
        if (! $this->hasBeenSent()) {
            return;
        }

        $url = $this->getSuccessUrl();
        $post = $this->getRequest()->getPost();
        $this->populate($post);
        $cube = $this->cube;

        foreach ($this->getElements() as $el) {
            $name = $el->getName();
            if (substr($name, 0, 16) === 'removeDimension_' && $el->getValue()) {
                $dimension = substr($name, 16);
                $cube->removeDimension($dimension);
                $url->setParam('dimensions', implode(',', $cube->listDimensions()));
                $this->redirectAndExit($url);
            }

            if (substr($name, 0, 16) === 'moveDimensionUp_' && $el->getValue()) {
                $dimension = substr($name, 16);
                $cube->moveDimensionUp($dimension);
                $url->setParam('dimensions', implode(',', $cube->listDimensions()));
                $this->redirectAndExit($url);
            }

            if (substr($name, 0, 18) === 'moveDimensionDown_' && $el->getValue()) {
                $dimension = substr($name, 18);
                $cube->moveDimensionDown($dimension);
                $url->setParam('dimensions', implode(',', $cube->listDimensions()));
                $this->redirectAndExit($url);
            }
        }

        if ($dimension = $this->getSentValue('addDimension')) {
            $dimensions = $url->getParam('dimensions');
            if (empty($dimensions)) {
                $dimensions = $dimension;
            } else {
                $dimensions .= ',' . $dimension;
            }
            $url->setParam('dimensions', $dimensions);

            $this->setSuccessUrl($url->without('addDimension'));
            $this->redirectOnSuccess($this->translate('New dimension has been added'));
        }
    }
}
