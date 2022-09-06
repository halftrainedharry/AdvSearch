- [AdvSearch Documentation](#advsearch-documentation)
  - [AdvSearchForm](#advsearchform)
    - [Properties](#properties)
    - [Ajax mode Properties](#ajax-mode-properties)
    - [Google Maps Properties](#google-maps-properties)
  - [AdvSearch](#advsearch)
    - [Properties](#properties-1)
    - [Paging Properties](#paging-properties)
    - [Properties for Extracts and Highlights](#properties-for-extracts-and-highlights)
    - [Ajax mode Properties](#ajax-mode-properties-1)
  - [AdvSearchGmapInfoWindow](#advsearchgmapinfowindow)
    - [Properties](#properties-2)
  - [Placeholders](#placeholders)
  - [Chunks](#chunks)
    - [AdvSearchForm](#advsearchform-1)
    - [AdvSearchResultsWindow](#advsearchresultswindow)
    - [AdvSearchResult](#advsearchresult)
    - [AdvSearchResults](#advsearchresults)
    - [AdvSearchPaging1](#advsearchpaging1)
    - [AdvSearchPaging2](#advsearchpaging2)
    - [AdvSearchPaging3](#advsearchpaging3)
    - [AdvSearchPageLink](#advsearchpagelink)
    - [AdvSearchCurrentPageLink](#advsearchcurrentpagelink)
  - [AdvSearch Glossary](#advsearch-glossary)

# AdvSearch Documentation

Dynamic content search add-on that supports results highlighting, faceted search and search in custom packages.

## AdvSearchForm

Basic usage:

```
[[!AdvSearchForm]]
```

### Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| asId | Unique id for AdvSearch instance. Used to distinguish several AdvSearch add-on instances on the same page. This token is used to link the snippet calls between them. Choose a short name. eg: 'as2'. a-z, _ , 0-9 (case sensitive) | as0 |
| addCss | to add the default css file to the web pages automatically. 0: not included. You should add the css manually. 1: included before the closing HEAD tag | 1 |
| addJs | to add the javascript files to the web pages automatically. 0: not included. You should add them manually. 1: included before the closing HEAD tag 2: included before the closing BODY tag| 1 |
| landing | the resource id that the AdvSearch snippet is called on, that will display the results of the search. Mandatory if you would like display the results on another page. | 0 => current document id |
| method | whether to send the search over POST or GET. 'POST' | 'GET' | GET |
| searchIndex | the name of the REQUEST parameter that the search will use. | search |
| searchString | to initialize the search with a default searchString. Used in conjunction with ``&init=`all` `` this allow to display search results for a specific search string. | '' => empty string |
| toPlaceholder | whether to set the output to directly return, or set to a placeholder with this propertys name. placeholder name | '' (empty string) => directly returned |
| tpl | the chunk that will be used to display the search form. | AdvSearchForm |

### Ajax mode Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| withAjax | whether to set on a new document (page reload), or set the results in div section thru ajax. | 0 => non-ajax mode |
| ajaxResultsId | The resource id which hold the AdvSearch snippet call. This document is called through ajax. It should be a plain/text document with empty template. Mandatory with the ajax mode | |
| addJQuery | to add the jquery library to the web pages automatically. 0: not included; 1: included before the closing HEAD tag; 2: included before the closing BODY tag | 1 |
| jsJQuery | Location of the Jquery javascript library. path to the Jquery library. change this parameter to set an newer jquery release. | assets/components/advsearch/js/jquery-1.5.1.min.js |
| liveSearch | to use the live search (i.e. results as typing) | 0 |
| urlScheme | indicates in what format the URL is generated. Options: -1, full, abs, http, https | | -1

### Google Maps Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| googleMapDomId | Id of the DOM element that contains the map | |
| googleMapLatTv | Name of the TV that contains the value for the latitude of the marker | |
| googleMapLonTv | Name of the TV that contains the value for the longitude of the marker | |
| googleMapMarkerTitleField | Name of the TV that contains the value for marker title | |
| googleMapMarkerWindowId | Resource ID that is called when a marker is clicked. This resource may call the snippet AdvSearchGmapInfoWindow. | |
| googleMapZoom | Default zoom value for the Google Map | 5 |
| googleMapCenterLat | Default value for the map's center position latitude | |
| googleMapCenterLong | Default value for the map's center position longitude | |

## AdvSearch

Basic usage:

```
[[!AdvSearch]]
```

### Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| asId | Unique id for AdvSearch instance. Used to distinguish several AdvSearch add-on instances on the same page. This token is used to link the snippet calls between them. Choose a short name. eg: 'as2'. a-z, _ , 0-9 (case sensitive) | as0 |
| tpl | The chunk that will be used to display the contents of each search result. | AdvSearchResult |
| containerTpl | the chunk that will be used to wrap all the search results, pagination and message. | AdvSearchResults |
| fields | The list of fields available with search results. comma separated list of fields | pagetitle,longtitle,alias,description,introtext,content |
| withFields | Define which fields are used for the search in fields of document resource. Comma separated list of fields from modResource. e.g: `pagetitle,introtext` : the search occurs only inside these 2 fields | pagetitle,longtitle,alias,description,introtext,content |
| includeTVs | Add TVs values to search results and set them as placeholders. Comma separated list of tv names | |
| withTvs | Define which Tvs are used for the search in Tvs. Comma separated list of tv names. e.g: `tv1,tv2,tv3` : the search occurs only inside these 3 TVs. | |
| sortby | comma separated list of couple "field [ASC|DESC]" to sort by. field could be : field name or TV name. Field name of joined resource are prefixed by resource name. e.g: equipComment_body | createdon DESC |
| **Limit resources to search in** | | |
| ids | A comma-separated list of IDs to restrict the search to. Use  the *GetIds* addon to specify a complex list of ids. | |
| parents | A comma-separated list of parent IDs to restrict the search to the direct children of these particular containers. | |
| contexts | The contexts to search in. Comma separated list of contexts | Defaults to the current context if none are explicitly specified. |
| hideContainers | Search in container resources. 0 => Search in all resources; 1 => Will not search in any resources marked as a container (is_folder). | 0 |
| hideMenu | Whether or not to search in documents that are hidden from the menu. 0 => Search only in documents visible from menu;· 1 => Search only in documents hidden from menu;· 2 => Search in both | 2 |
| hideLinks | Exclude Symlinks and Weblinks from the search | 0 |
| **Output** | | |
| output | output type. Opt: 'html', 'json'. 'json' : Array of all results as json string. 'html' : Page of results as html string | html |
| toPlaceholder | whether to set the output to directly return, or set to a placeholder with this propertys name. | '' (empty string) => directly returned |
| **Hooks** | | |
| queryHook | A query hook to change the default query. A snippet name to run as queryHook. See the [QueryHook documentation](QueryHooks_Documentation.md). | |
| postHook | A post hook to change the displaying of results. A snippet name to run as postHook. e.g: To display the results as a table rather than as a list use the posthook feature. | |
| postHookTpls | A comma separated list of chunks used by the postHook to change the display of results. e.g: To display the results as a table of results, you probably provide: the table header chunk and the row header chunk. | |
| **Request method and parameter Names** | | |
| method | whether to send the search over POST or GET. Options: 'POST', 'GET' | GET |
| searchIndex | the name of the REQUEST parameter that the search will use. | search |
| pageIndex | | |
| offsetIndex | The name of the offset parameter that the search will use. | offset |
| **Cache** | | |
| cacheQuery | Whether or not to cache the query | 0 |
| cacheTime | How long the query should be cached in seconds | 7200 (= 2 hours) |
| **Other settings** | | |
| maxWords | maximum number of words for searching | 20 |
| minChars | Minimum number of characters to require for a word to be valid for searching. 2 < int < 100 | 2 |
| init | defines if the search display all the results or none when the page is loaded at the first time. Options: 'none', 'all' | none |
| engine | Search engine selected | MySql |
| fieldPotency | potency per field. comma separated list of field : potency. Default potency to 1 if not set | createdon |
| searchString | to initialize the search with a default searchString. Used in conjunction with `&init=`all` ``. this allow to display search results for a specific search string. | |
| urlScheme | indicates in what format the URL is generated. Options: -1, full, abs, http, https | -1 => URL is relative to site_url |

### Paging Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| perPage | The number of search results to show per page. Set to 0 if unlimited. | 10 |
| pagingType | The type of pagination. 0 => No paging; 1 => Previous – X-Y/Z – Next; 2 => Result Pages 1 \| 2 \| 3; 3 => Previous 1 \| 2 \| ... \| 7 \| 8 \| 9 \| ... \| 14 \| 15 Next  | 1 |
| pagingTpl | The chunk to use for the whole paging navigation. | AdvSearchPaging1, AdvSearchPaging2 or AdvSearchPaging3 according to the pagingType setting |
| pageTpl | The chunk to use for a single page link. Used by pagingType 2 & 3. | AdvSearchPageLink |
| currentPageTpl | The chunk to use for the current page link. Used by pagingType 2 & 3. | AdvSearchCurrentPageLink |
| pagingSeparator | The string used to separate page links in the paging-navigation. Used by pagingType 2 & 3. | ' \| ' |
| paging3OuterRange | How many page links are visible at the start and the end of the navigation | 2 |
| paging3MiddleRange | How many page links are visible in the middle section of the navigation around the current page. Minimum: 3 | 3 |
| paging3RangeSplitterTpl | The chunk that is used for the content between middle and outer range | `@INLINE <span class="advsea-page"> ... </span>` |

### Properties for Extracts and Highlights

| Name | Description | Default |
| :--- | :--- | :--- |
| showExtract | show the search terms highlighted in one or several extract. string as nb: csv list of fields. 5 stands for 5:content. e.g: 3: introtext,content => 3 extracts displayed from introtext . content | '1:content' => One extract displayed from content field |
| extractTpl | The chunk that will be used to wrap each extract | Extract |
| extractEllipsis | ellipside to mark the start and the end of an extract when the sentence is cutting | '...' (three dots) |
| extractLength | length of separate extraction. 50 < Integer < 800 | 200 |
| highlightResults | create links so that search terms will be highlighted when linked page clicked. Requires Highlight plugin | 1 |
| highlightClass | The CSS class name to add to highlighted terms in results. | advsea-highlight |
| highlightTag | The html tag to wrap the highlighted term with in search results. | span |

### Ajax mode Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| effect | The effect to apply for the displaying of the window of results. See AdvSearch demo site. Options: Opt: basic, showfade, slidefade | basic |
| opacity | Opacity of the advSearch_output div where are returned the ajax results. Options: 0. (transparent) < Float < 1. (opaque) | 1 |
| moreResults | The resource id of the page you want the more results link to point to. Mandatory if you want more results. | 0 => no moreResults page |
| moreResultsTpl | The chunk name to use for the “More results” link. | PageLink |
| withAjax | whether to set on a new document (page reload), or set the results in div section thru ajax. Should be set to 1 with ajax mode. | 0 => non-ajax mode |

## AdvSearchGmapInfoWindow

Snippet for AdvSearch's GoogleMap infobox. The snippet returns information for the resource with the corresponding ID in the request parameter `$_GET['urlID']`.

Basic usage:

```
[[!AdvSearchGmapInfoWindow? &tpl=`tplGoogleMapInfobox`]]
```

### Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| withTVs | comma separated list of TV names to make available as placeholders | |
| tpl | Chunk name with the output template. Available placeholder are all the resource fields and the TVs defined in the property withTVs | |

## Placeholders

These placeholders are related to templates.

**[[+asId]]**<br>
AdvSearch identifier. This placeholder is required in the search form template to distinguish advSearch instances.

**[[+landing]]**<br>
Landing resource document id where are displayed results with non-ajax mode.

**[[+method]]**<br>
http request method used to send the form.

**[[+searchIndex]]**<br>
http variable name used for the search string.

**[[+searchValue]]**<br>
Search string value.

**[[+resultsWindow]]**<br>
`<div></div>` section where will be attached the search results window.

**[[+etime]]**<br>
Server elapsed time of the search.

**[[+fieldName]]**<br>
Any field value from the list of fields provided by the fields parameter. e.g: ``&fields=`pagetitle,introtext` ``. [[+pagetitle]] and [[+introtext]] available

**[[+TVName]]**<br>
Any TV value from the list of TVs provided by the withTVs and includeTVs parameter. e.g: ``&withTVs=`tv1,tv2` ``. [[+tv1]] and [[+tv2]] available

**[[+idx]]**<br>
Number of result. could be use inside the AdvSearchResult chunk to alternate class.

**[[+query]]**<br>
The search string used as query.

**[[+total]]**<br>
The total number of result found.

**[[+pagingType]]**<br>
Paging type. 1, 2 or 3

**[[+page]]**<br>
The current page number.

**[[+totalPage]]**<br>
The total number of result pages.

**[[+perPage]]**<br>
The maximum number of results per page.

**[[+offset]]**<br>
Offset of the current page.

**[[+first]]**<br>
Number of the first result of the current page.

**[[+last]]**<br>
Number of the last result of the current page.

**[[+separator]]**<br>
String used as separator between page link number for paging Type2.

**[[+liveSearch]]**<br>
liveSearch Boolean [0/1].

## Chunks

### AdvSearchForm

default chunk provided to style a search form

Should contain:
* a form id: `id="[[+asId]]"`
* an action: `action="[[~[[+landing]]]]"`
* a method: `method="[[+method]]"`
* an input of type hidden named asId: `<input type="hidden" name="asId" value="[[+asId]]" />`

And possibly:<br>
* an input text named `"[[+asId]]"_search`

### AdvSearchResultsWindow

default chunk provided to style the div section needed to set the ajax window of results. Should contain an id: `id="[[+asId]]_reswin"`

### AdvSearchResult

default chunk provided to style a search result.

Should contain:
* `[[+extracts]]` to get the extracts of result

### AdvSearchResults

default chunk provided to style a search result.

Should contain:
* `[[+resultInfo]]` to get the results info header
* `[[+paging]]` to get the pagination
* `[[+results]]` to get the search results

### AdvSearchPaging1

Default chunk provided to style the pagination type 1

Should contain:
* `[[+previousLink]]` to get the link to the
previous page
* `[[+nextLink]]` to get the link to the next page

### AdvSearchPaging2

Default chunk provided to style the pagination type 2.

### AdvSearchPaging3

Default chunk provided to style the pagination type 3.

### AdvSearchPageLink

Default chunk provided to style the page number links. Used with paging types 2 & 3.

### AdvSearchCurrentPageLink

Default chunk provided to style the current page number link.<br>
Used with paging types 2 & 3.

## AdvSearch Glossary

**Ajax mode**<br>
Search results are displayed in the current page through AJAX request.

**Non ajax mode**<br>
Search results are displayed in a new page (page reloading).

**Search engine**<br>
AdvSearch could runs with two different engine: mysql and zend+mysql. Zend engine allows to do a search in an index repository first before to get the results from the mysql database. This needs to index documents first. For performance reasons, this engine should be reserved for search in dynamic contents.

**Search in dynamic contents**<br>
Mysql database store only static contents. If a document has one or several MODx tags (chunk, tvs, snippet calls …) the parsed results are not stored in the database. To do a search in parsed resources, the parsed documents' should be first processed and search terms found in these documents stored in an additional repository (a folder). Then this repository is used in
conjunction with MySQL to provide search results.

**Search term**<br>
The entry term in the search form

**Extract**<br>
Part of a document extracted and added in the results page

**Search result**<br>
Title (as a link to the document), description and extract(s)

**Search results page**<br>
All the search results with optionaly a more results link to a

**showMoreResult page.**<br>
or all the search results paginated with the perPage parameter

**Highlighted term**<br>
In the search results page, the search terms found could be (or not) highlighted.

**PostHook**<br>
A snippet which allow to modify on-fly the displayed results

**QueryHook**<br>
A snippet which, starting http variables, modify on-fly the default query

**Faceted search**<br>
Search with filters based on document fields and Tvs. By using the appropriate search form and queryHook you could set up all kind of faceted search you want. With or without search term input fields.

**Search in custom packages**<br>
By default advSearch runs with the modResource package (documents + possibly template variables)<br>
By using the “Main” and “Joined” declarations of the queryHook, you could add joined resources to the modResource package or replace modResource by an another package (+ possibly joined resources). See the query hook documentation for futher information.
