(function($) {
'use strict';

var categories, countryData, categoryMap, MAX_CATEGORY_LEVELS = 5;

$.fn.categorySelector = function(categoriesEndpoint, countryDataEndpoint, currentCategoryId) {
    this.html('<input type=hidden name="category_id">');
    var elem = this.get(0);

    categories = JSON.parse($("#category_tree").text());
    countryData = JSON.parse($("#country_data").text());
    categoryMap = {};

    function formatDict(template, fmt) {
        // NOTE: This part differs from the backend and feels hackier to me
        //       but is based on http://stackoverflow.com/a/5344074/1796894
        var templStr = JSON.stringify(template);
        for(var prop in fmt) {
            if(fmt.hasOwnProperty(prop)) {
                templStr = templStr.replace(new RegExp('{'+prop+'}', 'g'), fmt[prop]);
            }
        }
        return JSON.parse(templStr, fmt);
    }

    function prepareWorldCategory(worldCat) {
        worldCat.subcategories = [];
        for(var continent of countryData.continents) {
            var continentCat = formatDict(worldCat.continent_template, continent);
            continentCat.subcategories = []
            for(var country of continent.countries) {
                continentCat.subcategories.push(formatDict(worldCat.country_template, country))
            }
            worldCat.subcategories.push(continentCat);
        }
    }

    function walker(categories, parent) {
        for(var cat of categories) {
            cat.parent = parent;
            categoryMap[cat._id] = cat;
            if(cat.type === 'world') {
                // TODO: I am writing this twice QQ
                //       See categories.py on the backend
                prepareWorldCategory(cat);
            }
            walker(cat.subcategories, cat);
        }
    }
    walker(categories);

    createDropdownsFromCategories(elem, null, categories);
    selectFromCategories(elem, categories, currentCategoryId);

    return this;
};

function createDropdownsFromCategories(dropdownsRoot, parent, categories, path) {
    if(path == null) path = [];
    path.push(parent && parent._id);
    var level = path.length;
    if(level <= MAX_CATEGORY_LEVELS) {
        var filteredCategories = categories.filter(function(cat) { return cat.can_post; });
        if(filteredCategories.length > 0) {
            var sElem = document.createElement("select");
            sElem.className = 'cat-' + (parent && parent._id);
            sElem.onchange = function(event) {
                var selectedCat;
                for(var cat of filteredCategories) {
                    if(cat._id === event.target.value) {
                        selectedCat = cat;
                        break;
                    }
                }
                if(!selectedCat) {
                    selectedCat = categoryMap[parent && parent._id];
                }

                selectCategory(dropdownsRoot, selectedCat);
            };
            dropdownsRoot.appendChild(sElem);
            if(parent != null) {
                sElem.options.add(new Option('-- None --', null));
            }
            for(var cat of filteredCategories) {
                [cat.selectElem, cat.path] = createDropdownsFromCategories(dropdownsRoot, cat, cat.subcategories, path.slice());
                var option = new Option(cat.name, cat._id);
                sElem.options.add(option);
                cat.optionElem = option;
            }
            return [sElem, path];
        }
    } else {
        console.warn('List for '+(parent && parent._id)+' found at level '+level+' which is too deep');
    }
    return [null, path];
};

function selectFromCategories(dropdownsRoot, categories, selectedCategoryId) {
    var firstSelectableCategory,
        foundSelectedCategory = false;

    function walker(categories, level = 1) {
        if(foundSelectedCategory) return;
        if(level <= MAX_CATEGORY_LEVELS) {
            for(var cat of categories) {
                walker(cat.subcategories, level + 1);
                if(cat.subcategories.length <= 0) {
                    if(!firstSelectableCategory) {
                        firstSelectableCategory = cat;
                    }
                }
                if(selectedCategoryId && selectedCategoryId === cat._id) {
                    foundSelectedCategory = true;
                    selectCategory(dropdownsRoot, cat);
                    break;
                }
            }
        } else {
            console.warn('List found at level '+level+' which is too deep');
        }
    };
    walker(categories);

    if(!foundSelectedCategory) {
        if(firstSelectableCategory) {
            selectCategory(dropdownsRoot, firstSelectableCategory);
        } else {
            return false;
        }
    }
    return true;
};


function selectCategory(dropdownsRoot, category) {
    // NOTE: this feels kind of kludgy, but...
    // http://stackoverflow.com/a/17958847/1796894
    // TODO: Generalize this??
    // $timeout(() => { this.categoryId = category._id; });

    var path = category.path;

    Array.from(dropdownsRoot.children).forEach(function(dropdown) {
        if(path.some(function(id) { return 'cat-'+id === dropdown.className; })) {
            dropdown.style.display = 'inline-block';
        } else {
            dropdown.style.display = 'none';
        }
    });

    for(var id of path) {
        parentDropdownSearchLoop:
        Array.from(dropdownsRoot.children).forEach(function(node) {
            if(node.nodeName === 'INPUT') {
                node.value = category._id;
            } else if(node.nodeName === 'SELECT') {
                Array.from(node.options).forEach(function(option) {
                    if(option.value === id) {
                        option.selected = true;
                    }
                });
            }
        });
    }
};

}(jQuery));
