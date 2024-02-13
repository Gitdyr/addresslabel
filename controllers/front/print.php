<?php
/**
 * NOTICE OF LICENSE
 *
 *  @author    Kjeld Borch Egevang
 *  @copyright 2016 Kjeld Borch Egevang
 *  @license   All rights reserved
 *
 *  $Date: 2016/11/08 04:06:15 $
 *  E-mail: kjeld@mail4us.dk
 */

/**
 * @since 1.5.0
 */
class AddresslabelPrintModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $addresslabel = new Addresslabel();
        $addresslabel->printLabel();
        exit(0);
    }
}
