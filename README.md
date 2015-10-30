## Section Information

This function returns section information, which varies depending on the type of page you're viewing. Several conditional checks are combined into this one function so that you can get comprehensive section data form one function, rather than an exhaustive list of conditional statements.

It supports custom post type pages: e.g., if you have a CPT of "movies," creating a page with the slug of "movies" will return data from the page, rather than data from the post type object. This is useful for keeping complicated post type archive content in the CMS.

### Parameters

`check_for_page`: Whether to check for a page with a matching slug when on a post type/taxonomy archive/single. Will also perform the check for pages with a slug of 'search' and '404'. Default 'true'.

`post_type_pages`: The function will default to the name of the post type for post type page checks, but you can manually override it with this parameter. Includes defaults for Search and 404.

### Returns

```
$ancestor = array(
	'id'          => Ancestor ID,
	'title'       => Ancestor title,
	'name'        => Ancestor slug,
	'object'      => Ancestor post/term object,
	'found_posts' => Conditional. Number of search results
);
```

### Filters

You can adjust the output of this function by adding this filter: `add_filter( 'cnp_get_highest_ancestor', 'custom_filter_title', 20, 1 )`. The accepted argument is the `ancestor` variable. Return it in your filter function to alter the output.

### Scenarios

#### Hierarchical post type (_is\_post\_type\_hierarchical_)

Returns data about the highest ancestor of the current post. If the current post is a top-level post, then the current post data is returned.

#### Flat post type (else)

Returns data about the post type. Will check for a page with the same slug as the post type if `$args['check\_for\_page']` is true, returns basic post type data otherwise.

#### Default home page (_is\_home_ && _is\_front\_page_)

Returns basic home page data.

#### Static Front Page (_page\_on\_front_)

Returns data based on the Front Page post object, which is defined in the Reading Settings.

#### Posts Page (_page\_for\_posts_)

Returns data based on the Posts Page, which is defined in the Reading Settings.

#### Taxonomy Terms (_is\_tax, is\_category, is\_tag_)

Returns data based on the current taxonomy term.

#### Search Results (_is\_search_)

Returns basic search results data.

#### 404 Page (_is\_404_)

Returns basic 404 page data.