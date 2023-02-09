<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$this->addExternalCss($this->__component->__path."/assets/jquery-ui.css");
$this->addExternalJS($this->__component->__path."/assets/jquery-ui.min.js");


if(!empty($arResult['ERROR'])){
    foreach ($arResult['ERROR'] as $error) {
        ShowMessage($error);
    }
}else{
?>
    <div class="sdownload-butt">
        <a href="<?=$arResult['PATH_TO_PRICELIST']?>">Скачать прайс лист</a>
    </div>
<? } ?>