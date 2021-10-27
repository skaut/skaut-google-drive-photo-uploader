# Photo Uploader pro Skautí fotobanku

**Tento plugin pro Wordpress díky propojení pluginů Gravity Forms a Use-Your-Drive umožňuje nahrávání fotografií na Google Drive skautské fotobanky.**
Plugin na základě dat vyplněných ve formuláři generuje fixně formátované popisky pro každý nahrávaný soubor (fotku) a umožňuje
tak do fotobanky přenášet metadata, která jsou pro organizaci fotobanky klíčové.

## Popis funkcionality

Funkcionalita tohoto pluginu obsahuje následující:

- Nahrávací formulář je rozdělen na dvě stránky. Na první straně uživatel vyplňuje detaily o fotoalbu a na druhé straně vybírá fotografie k nahrání.
- Na základě administrátorem definované šablony vytvoří plugin z údajů výplněných v prvním kroku formátovaný popisek.
- Popisek bude umístěn ke každému nahrávanému soubore do pole `description` na Google Drive.
- Formulář navíc předvyplňuje některá formulářové pole na základě údajů z uživatelského účtu a po prvním vyplnění také z předchozího vyplněného formuláře.

*Plugin ovlivňuje funkcionalitu jakéhokoliv formuláře, který má v nastavení uvedenou CSS třídu `photo-uploader`. Plugin upravuje nahrávání jakéhokoliv
Use-your-Drive upload boxu, který obsahuje CSS třídu `add_description`.*

## Instalace
Jak je zmíněno výše, tento plugin propojuje funkcionalitu dvou pluginů třetích stran - [Gravity Forms](https://www.gravityforms.com/) 
a [Use-your-Drive](https://www.wpcloudplugins.com/plugins/use-your-drive-wordpress-plugin-for-google-drive/). Tyto dva pluginy jsou
tedy nutné k využití funkcionality tohoto pluginu.

### Předpoklady
- Wordpress 5.7.3 a vyšší
- Gravity Forms 2.5.9 a vyšší
- Use-your-Drive 1.17.6 a vyšší

### Proces instalace
1. Nainstalujte pluginy Gravity Forms a Use Your Drive.
1. Propojte plugin Use Your Drive s Google účtem a proveďte jeho úvodní nastavení.
1. Proveďte úvodní nastavení pluginu Gravity Forms.
1. Zabalte obsah celého repozitáře do zip balíčku a ten nainstalujte do Wordpressu jako plugin.
1. Importujte formulář ze souboru `gravityforms-export.json`, který je součástí tohoto pluginu.
1. Vytvořte stránku a vložte do ní vytvořený formulář např. pomocí shortcodu `[gravityform id="x"]`.

## Konfigurace pluginu

V tuto chvíli lze z administrace Wordpressu kromě jakýchkoliv běžných úprav formulářových polí provádět dva úkony. Předně přidávání
nových formulářových polí a jejich přidání do popisku ukládaného do Google Drive a druhak úprava chování pluginu Use-your-Drive pro
nahrávání fotografií.

_V tuto chvíli nelze v administraci jednoduše definovat další hodnoty, které bude formulář předvyplňovat ať už na základě uživatelských účtů či
na základě předchozích vyplnění formuláře. Tato změna vyžaduje zásah do kódu pluginu._ 

### Vytvoření nového pole a přidání do Google Drive popisku
Plugin umožňuje přímo z administrace Wordpressu přidávat či odebírat formulářová pole. Data z jednotlivých polí formuláře jsou do
formátovaného popisku vkládána na základně admin labelů nastavených u jednotlivých polí. Každé pole formuláře má tedy nastaven tento
popisek podle kterého je pak možné hodnotu tohoto pole umístit nejen do popisku ale i notifikačních e-mailů či do zprávy zobrazené 
po odeslání formuláře.

Formát popisku, který se zapisuje do Google Drive je definovýn šablonou, která je jako JS snippet uložená v HTML poli na začátku 
druhé stránky formuláře. V tomto poli je uvedena šablona, ve které budou při vykreslování formuláře nahrazeny zástupné symboly hodnotami
z vyplněného formuláře. Zástupné symboly jsou ve formátu `%nazev_pole%`, kde vše mezi znaky `%` musí korespondovat s admin labelem jednoho 
z formulářových polí. Například zástupný symbol `%first_name%` bude nahrazen hodnotou, kterou uživatel vyplnil do pole, které má admin label
nastaven na `first_name`.

Při přidání nového pole do formuláře tedy pro jeho přidání do popisku na Google Drive stačí nastavit poli admin label a potom v šabloně uložené
v HTML poli na začátku druhé stránky formuláře přidat zástupný symbol do libovolného umístění.

_Výchozí snippet kódu se šablonou popisku najdete níže. Snippet je ale také uložen v souboru `gravityforms-export.json`, 
který je součástí tohoto pluginu a obsahuje export konfigurace celého formuláře._

```
<script type="text/javascript">
    window.descriptionTemplate = "%title%\n";
    window.descriptionTemplate += "%consents%, %copyright%, %note%, %id%, %first_name% %last_name% - %nickname%, ";
    window.descriptionTemplate += "%phone%, %email%, %department%, %group%, %public%, kraj %district%, %location%, ";
    window.descriptionTemplate += "%age_category%, %start%, %end%, %keywords%\n";
    window.descriptionTemplate += "%title%\n";
    window.descriptionTemplate += "%description%\n";
    window.descriptionTemplate += "Autor: %copyright%";
</script>
```  

### Úprava chování Use-your-Drive

Přímo v administraci je možné jakkoliv měnit chování boxu pro nahrávání fotografií na Google Drive. Pro úpravu chování stačí v administraci kliknout
na pole Use-your-Drive a následně na modré tlačítko "Build your shortcode". Následně lze měnit jakékoliv atributy pluginu. Výrazně doporučuji při
jakémkoliv zásahu otestovat, zda a jak nové nastavení funguje. Pro jistotu níže uvádím výchozí konfiguraci (tj. výsledný shortcode) pro případ potřeby
návratu k původnímu fungování.

```
[useyourdrive class="gf_upload_box add_description" dir="toto doplnit pomocí generátoru shortcodu" 
account="toto doplnit pomocí generátoru shortcodu" subfolder="%id% - %user_login% %yyyy-mm-dd%" mode="upload" 
viewrole="administrator|author|contributor|editor|subscriber|guest" userfolders="auto" viewuserfoldersrole="none" 
downloadrole="none" upload="1" upload_folder="0" upload_auto_start="0" uploadrole="all" uploadext="jpg|png|gif|tiff" ]
```

_Na CSS třídu add_description je navázána funkcionalitu pro vkládání popisku - neměnit!_

## Autor

Tento plugin byl pro Skaut vyvinul Honza Kopecký. V případě jakýchkoliv dotazů čí problémů se obracejte na [honza.kopecky95@gmail.com](mailto:honza.kopecky95@gmail.com). 