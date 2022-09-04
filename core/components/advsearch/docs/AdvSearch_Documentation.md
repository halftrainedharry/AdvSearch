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
| asId | Unique id for AdvSearch instance. Used to distinguish several AdvSearch add-on instances on the same page. This token is used to link the snippet calls between them. Choose a short name. eg: ‘as2’. a-z, _ , 0-9 (case sensitive) | as0   |
| clearDefault | clearing default text. Include the clear default js function in header and add the class "cleardefault" to the input text form.| 1 |
| addCss | to add the default css file to the web pages automatically. 0: not included. You should add the css manually. 1: included before the closing HEAD tag | 1 |
| addJs | to add the javascript files to the web pages automatically. 0: not included. You should add them manually. 1: included before the closing HEAD tag 2: included before the closing BODY tag| 1 |
| jsSearchForm | full path name of the javascript file to link with the form. This js file include the clearDefault function and could be modified to add custom features. full path name under /assets | assets/components/advsearch/js/advSearchForm.min.js |
| landing | the resource id that the AdvSearch snippet is called on, that will display the results of the search. Mandatory if you would like display the results on another page. | 0 => current document id |
| method | whether to send the search over POST or GET. ‘POST’ | ‘GET’ | GET |
| searchIndex | the name of the REQUEST parameter that the search will use. | search |
| searchString | to initialize the search with a default searchString. Used in conjunction with ``&init=`all` `` this allow to display search results for a specific search string. | '' => empty string |
| toPlaceholder | whether to set the output to directly return, or set to a placeholder with this propertys name. placeholder name | '' (empty string) => directly returned |
| tpl | the chunk that will be used to display the search form. | AdvSearchForm |

#### Ajax mode Properties
| Name | Description | Default |
| :--- | :--- | :--- |
| withAjax | whether to set on a new document (page reload), or set the results in div section thru ajax. | 0 => non-ajax mode |
| ajaxResultsId | The resource id which hold the AdvSearch snippet call. This document is called through ajax. It should be a plain/text document with empty template. Mandatory with the ajax mode |  |
| addJQuery | to add the jquery library to the web pages automatically. 0: not included; 1: included before the closing HEAD tag; 2: included before the closing BODY tag | 1 |
| jsJQuery | Location of the Jquery javascript library. path to the Jquery library. change this parameter to set an newer jquery release. | assets/components/advsearch/js/jquery-1.5.1.min.js |
| liveSearch | to use the live search (i.e. results as typing) | 0 |
| urlScheme | indicates in what format the URL is generated. Options: -1, full, abs, http, https |  | -1


## AdvSearch

Basic usage:

```
[[!AdvSearch]]
```

### Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| asId | Unique id for AdvSearch instance. Used to distinguish several AdvSearch add-on instances on the same page. This token is used to link the snippet calls between them. Choose a short name. eg: ‘as2’. a-z, _ , 0-9 (case sensitive) | as0 |
| containerTpl | the chunk that will be used to wrap all the search results, pagination and message. | AdvSearchResults |
| contexts | The contexts to search. comma separated list of contexts | Defaults to the current context if none are explicitly specified. |
| currentPageTpl | The chunk to use for a pagination link. | CurrentPageLink |
| fields | The list of fields available with search results. comma separated list of fields | pagetitle,longtitle,alias,description,introtext,content |
| engine | Search engine selected | MySql |
| extractEllipsis | ellipside to mark the start and the end of an extract when the sentence is cutting | '...' (three dots) |
| extractLength | length of separate extraction. 50 < Integer < 800 | 200 |
| extractTpl | The chunk that will be used to wrap each extract | Extract |
| fieldPotency | potency per field. comma separated list of field : potency. Default potency to 1 if not set | createdon |
| highlightClass | The CSS class name to add to highlighted terms in results. | advsea-highlight |
| highlightResults | create links so that search terms will be highlighted when linked page clicked. Requires Highlight plugin | 1 |
| highlightTag | The html tag to wrap the highlighted term with in search results. | span |
| hideContainers | Search in container resources. 0 : search in all resources. 1 : will not search in any resources marked as a container (is_folder). | 0 |
| hideMenu | search in hidden documents from menu. 0 : search only in documents visible from menu.· 1 : search only in documents hidden from menu.· 2 : search in hidden or visible documents
from menu | 2 |
| ids | Comma-delimited list of ids to search in. Use GetIds addon to specify complex list of ids. | '' => empty string |
| includeTVs | Add TVs values to search results and set them as placeholders. Comma separated list of tv names | '' => empty string |
| init | defines if the search display all the results or none when the page is loaded at the first time. Options: 'none', 'all' | none |
| maxWords | maximum number of words for searching | 20 |
| method | whether to send the search over POST or GET. Options: ‘POST’, ‘GET’ | GET |
| minChars | Minimum number of characters to require for a word to be valid for searching. 2 < int < 100 | 2 |
| offsetIndex | The name of the offset parameter that the search will use. | offset |
| output | output type. Opt: ‘html’, ‘json’. 'json' : Array of all results as json string. 'html' : Page of results as html string | html |
| pagingType | selection of the type of pagination. 1: Previous - X-Y /Z – Next. 2: Results Pages 1 \| 2 \| 3 | 1 |
| pageTpl | The chunk to use for a pagination link. Used only by paging0 type. | PageLink |
| paging1Tpl | The chunk to use for the paging type 1. | Paging1 |
| paging2Tpl | The chunk to use for the paging type 2. | Paging2 |
| pagingSeparator | String used to separate number page links. Used only by paging0 type. | ' \| ' |
| perPage | Set to the max number of results you would like on each page. Set to 0 if unlimited. | 10 |
| postHook | A post hook to change the displaying of results. A snippet name to run as postHook. e.g: To display the results as a table rather than as a list use the posthook feature. |  |
| postHookTpls | A comma separated list of chunks used by the postHook to change the display of results. e.g: To display the results as a table of results, you probably provide: the table header chunk and the row header chunk. |  |
| queryHook | A query hook to change the default query. A snippet name to run as queryHook. See the [QueryHook documentation](QueryHooks_Documentation.md). |  |
| searchString | to initialize the search with a default searchString. Used in conjunction with `&init=`all` ``. this allow to display search results for a specific search string. |  |
| showExtract | show the search terms highlighted in one or several extract. string as nb: csv list of fields. 5 stands for 5:content. e.g: 3: introtext,content => 3 extracts displayed from introtext . content | '1:content' => One extract displayed from content field |
| sortby | comma separated list of couple "field [ASC|DESC]" to sort by. field could be : field name or TV name. Field name of joined resource are prefixed by resource name. e.g: equipComment_body | createdon DESC |
| toPlaceholder | whether to set the output to directly return, or set to a placeholder with this propertys name. | '' (empty string) => directly returned |
| tpl | The chunk that will be used to display the contents of each search result. | AdvSearchResult |
| urlScheme | indicates in what format the URL is generated. Options: -1, full, abs, http, https | -1 => URL is relative to site_url |
| withFields | Define which fields are used for the search in fields of document resource. Comma separated list of fields from modResource. e.g: `pagetitle,introtext` : the search occurs only inside these 2 fields | pagetitle,longtitle,alias,description,introtext,content |
| withTvs | Define which Tvs are used for the search in Tvs. Comma separated list of tv names. e.g: `tv1,tv2,tv3` : the search occurs only inside these 3 TVs. |  |

### Ajax mode Properties

| Name | Description | Default |
| :--- | :--- | :--- |
| effect | The effect to apply for the displaying of the window of results. See AdvSearch demo site. Options: Opt: basic, showfade, slidefade | basic |
| opacity | Opacity of the advSearch_output div where are returned the ajax results. Options: 0. (transparent) < Float < 1. (opaque) | 1 |
| moreResults | The resource id of the page you want the more results link to point to. Mandatory if you want more results. | 0 => no moreResults page |
| moreResultsTpl | The chunk name to use for the “More results” link. | PageLink |
| withAjax | whether to set on a new document (page reload), or set the results in div section thru ajax. Should be set to 1 with ajax mode. | 0 => non-ajax mode |

## Placeholders

These placeholders are related to templates.

[[+asId]]
: AdvSearch identifier. This placeholder is required in the search form template to distinguish advSearch instances.

[[+landing]]
: Landing resource document id where are displayed results with non-ajax mode.

[[+method]]
: http request method used to send the form.

[[+searchIndex]]
: http variable name used for the search string.

[[+searchValue]]
: Search string value.

[[+resultsWindow]]
: `<div></div>` section where will be attached the search results window.

[[+etime]]
: Server elapsed time of the search.

[[+fieldName]]
: Any field value from the list of fields provided by the fields parameter. e.g: ``&fields=`pagetitle,introtext` ``. [[+pagetitle]] and [[+introtext]] available

[[+TVName]]
: Any TV value from the list of TVs provided by the withTVs and includeTVs parameter. e.g: ``&withTVs=`tv1,tv2` ``. [[+tv1]] and [[+tv2]] available

[[+idx]]
: Number of result. could be use inside the AdvSearchResult chunk to alternate class.

[[+query]]
: The search string used as query.

[[+total]]
: The total number of result found.

[[+pagingType]]
: Paging type. 1 or 2

[[+page]]
: The current page number.

[[+totalPage]]
: The total number of result pages.

[[+perPage]]
: The maximum number of results par page.

[[+offset]]
: Offset of the current page.

[[+first]]
: Number of the first result of the current page.

[[+last]]
: Number of the last result of the current page.

[[+separator]]
: String used as separator between page link number for paging Type2.

[[+liveSearch]]
: liveSearch Boolean [0/1].

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

### ResultsWindow

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

### Paging1

default chunk provided to style the pagination type 1

Should contain:
* `[[+previousLink]]` to get the link to the
previous page
* `[[+nextLink]]` to get the link to the next page

### Paging2

default chunk provided to style the pagination type 2.

### PageLink

default chunk provided to style the page number links. Used with paging type 0 only.

### CurrentPageLink

default chunk provided to style the current page number link.<br>
Used with paging type 0 only.

## AdvSearch Glossary

Ajax mode
: Search results are displayed in the current page through AJAX request.

Non ajax mode
: Search results are displayed in a new page (page reloading).

Search engine
: AdvSearch could runs with two different engine: mysql and zend+mysql. Zend engine allows to do a search in an index repository first before to get the results from the mysql database. This needs to index documents first. For performance reasons, this engine should be reserved for search in dynamic contents.

Search in dynamic contents
: Mysql database store only static contents. If a document has one or several MODx tags (chunk, tvs, snippet calls …) the parsed results are not stored in the database. To do a search in parsed resources, the parsed documents' should be first processed and search terms found in these documents stored in an additional repository (a folder). Then this repository is used in
conjunction with MySQL to provide search results.

Search term
: The entry term in the search form

Extract
: Part of a document extracted and added in the results page

Search result
: Title (as a link to the document), description and extract(s)

Search results page
: All the search results with optionaly a more results link to a

showMoreResult page.
: or all the search results paginated with the perPage parameter

Highlighted term
: In the search results page, the search terms found could be (or not)

highlighted.
: needs the plugin searchHighlight

PostHook
: A snippet which allow to modify on-fly the displayed results

QueryHook
: A snippet which, starting http variables, modify on-fly the default query

Faceted search
: Search with filters based on document fields and Tvs. By using the appropriate search form and queryHook you could set up all kind of faceted search you want. With or without search term input fields.

Search in custom packages
: By default advSearch runs with the modResource package (documents + possibly template variables)
: By using the “Main” and “Joined” declarations of the queryHook, you could add joined resources to the modResource package or replace modResource by an another package (+ possibly joined resources). See the query hook documentation for futher information.
