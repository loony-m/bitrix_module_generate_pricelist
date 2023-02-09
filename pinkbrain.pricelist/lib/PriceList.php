<?

namespace Pinkbrain;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\Elements\ElementCatalogTable;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\ORM\Query\Join;


class PriceList
{
    const IBLOCK_CODE = 'aspro_optimus_catalog';

    public static function getPathFile()
    {
        return [
            'PATH_TO_FILE' => '/upload/price-list.xlsx',
            'FULL_PATH_TO_FILE' => $_SERVER['DOCUMENT_ROOT'].'/upload/price-list.xlsx',
        ];
    }

    public function getIblockId()
    {
        $iblockID = IblockTable::getList(
            [
                'select' => ['ID'],
                'filter' => ['=CODE' => self::IBLOCK_CODE],
                'cache' => ['ttl' => 86400]
            ]
        )->fetch()['ID'];

        return $iblockID;
    }

    public function getList()
    {
        $iblockID = self::getIblockId();

        $arResult = [];

        $cache = Cache::createInstance();
        $cacheDir = 'catalog_element';
        $cacheKey = 'catalog_element';


        if ($cache->initCache(86400, $cacheKey, $cacheDir)) {
            $arResult = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cacheDir);

            $arSection = SectionTable::getList([
                'filter' => ['ACTIVE' => 'Y', 'IBLOCK_ID' => $iblockID],
                'select' => ['ID', 'NAME', 'DEPTH_LEVEL'],
                'order' => ['LEFT_MARGIN' => 'ASC'],
            ])->FetchAll();

            foreach ($arSection as $section) {
                $arResult['SECTION'][$section['ID']] = $section;
            }

            $arElement = ElementCatalogTable::getList([
                'select' => [
                    'NAME',
                    'ID',
                    'IBLOCK_SECTION_ID',
                    'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
                    'CML2_ARTICLE_' => 'CML2_ARTICLE',
                    'PRICE_TYPE_2_' => 'PRICE_TYPE_2',
                    'PRICE_TYPE_3_' => 'PRICE_TYPE_3',
                    'PRICE_TYPE_4_' => 'PRICE_TYPE_4',
                    'PRICE' => 'PRICES.PRICE',
                ],
                'filter' => ['IBLOCK_ID' => $iblockID, 'ACTIVE' => 'Y', 'PRODUCT.AVAILABLE' => 'Y'],
                'runtime' => [
                    new ReferenceField(
                        'PRICES',
                        PriceTable::class,
                        Join::on('this.ID', 'ref.PRODUCT_ID')
                    ),
                    new ReferenceField(
                        'PRODUCT',
                        ProductTable::class,
                        Join::on('this.ID', 'ref.ID')
                    ),
                ]
            ])->FetchAll();

            foreach ($arElement as $element) {
                $element['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl($element['DETAIL_PAGE_URL'], $element, true, 'E');
                $arResult['PRODUCT'][$element['IBLOCK_SECTION_ID']][] = $element;
            }

            $CACHE_MANAGER->RegisterTag('iblock_id_'.$iblockID);
            $CACHE_MANAGER->EndTagCache();
            $cache->endDataCache($arResult);
        }

        return $arResult;
    }

    public function generate()
    {
        $colorCell = '257fbd';
        $colorCellLevel1 = $colorCell;
        $colorCellLevel2 = '4cb2fa';
        $colorCellLevel3 = 'ddfbff';
        $fillType = Fill::FILL_SOLID;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $date = date("d.m.Y");

        $sheet->setCellValue('A1', 'Прайс-лист от '.$date)->getColumnDimension('I')->setCollapsed(true);
        $sheet->setCellValue('A2', 'Описание 1');
        $sheet->setCellValue('A3', 'Контакты: +7 (999) 999-99-99; +7 (999) 999-99-99');
        $sheet->setCellValue('A4', 'E-mail: test@bk.ru');
        $sheet->setCellValue('A5', 'Время работы с 00:00 до 00:00');

        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2:H2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A3:H3')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A5:H5')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A6:H6')->getAlignment()->setHorizontal('center');

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');
        $sheet->mergeCells('A5:H5');

        $sheet->getStyle('A1:H1')->getFill()->setFillType($fillType)->getStartColor()->setARGB($colorCell);
        $sheet->getStyle('A2:H2')->getFill()->setFillType($fillType)->getStartColor()->setARGB($colorCell);
        $sheet->getStyle('A3:H3')->getFill()->setFillType($fillType)->getStartColor()->setARGB($colorCell);
        $sheet->getStyle('A4:H4')->getFill()->setFillType($fillType)->getStartColor()->setARGB($colorCell);
        $sheet->getStyle('A5:H5')->getFill()->setFillType($fillType)->getStartColor()->setARGB($colorCell);

        $sheet->getStyle('A6:I6')->getFont()->setBold(true);
        $sheet
            ->setCellValue('A6', 'Наименование')
            ->setCellValue('B6', 'Артикул')
            ->setCellValue('C6', 'Опт 1')
            ->setCellValue('D6', 'Опт 2')
            ->setCellValue('E6', 'Опт 3')
            ->setCellValue('F6', 'Опт 4')
            ->setCellValue('G6', 'Статус')
            ->setCellValue('H6', 'Кол-во для заказа');


        $arElement = self::getList();
        $startRow = 7;
        $domain = 'https://'.$_SERVER['SERVER_NAME'];

        foreach ($arElement['SECTION'] as $section) {
            $colorSectionCell = $colorCell;
            switch($section['DEPTH_LEVEL']) {
                case 2:
                    $colorSectionCell = $colorCellLevel2;
                    break;
                case 3:
                    $colorSectionCell = $colorCellLevel3;
                    break;
            }

            $range = 'A'.$startRow.':H'.$startRow;
            $activeCell = 'A'.$startRow;

            $sheet->setCellValue($activeCell, $section['NAME']);
            $sheet->mergeCells($range);
            $sheet->getStyle($range)->getFill()->setFillType($fillType)->getStartColor()->setARGB($colorSectionCell);
            $sheet->getStyle($range)->getFont()->setBold(true);

            $startRow++;

            if(count($arElement['PRODUCT'][$section['ID']]) > 0){
                foreach ($arElement['PRODUCT'][$section['ID']] as $product) {
                    $sheet
                        ->setCellValue('A'.$startRow, $product['NAME'])
                        ->setCellValue('B'.$startRow, $product['CML2_ARTICLE_VALUE'])
                        ->setCellValue('C'.$startRow, $product['PRICE'])
                        ->setCellValue('D'.$startRow, $product['PRICE_TYPE_2_VALUE'])
                        ->setCellValue('E'.$startRow, $product['PRICE_TYPE_3_VALUE'])
                        ->setCellValue('F'.$startRow, $product['PRICE_TYPE_4_VALUE'])
                        ->setCellValue('G'.$startRow, 'В наличии')
                        ->setCellValue('H'.$startRow, '');

                    $sheet->getCell('A'.$startRow)->getHyperlink()->setUrl($domain . $product['DETAIL_PAGE_URL']);

                    $startRow++;
                }
            }
        }

        foreach(range('A','I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(self::getPathFile()['FULL_PATH_TO_FILE']);
    }

    public static function agentRunGenerate()
    {
        self::generate();

        return "\Pinkbrain\Pricelist::agentRunGenerate();";
    }
}