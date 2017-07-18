document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('category_tree') != null) {
        var categoryTree = document.getElementById('category_tree').innerText;
        categoryTree = '{"root": ' + categoryTree + '}';
        var categories = JSON.parse(categoryTree)['root'];
        var dropdownsRoot = document.getElementsByClassName('category-dropdowns')[0];

        var refreshCategoryDropdowns = function () {
            dropdownsRoot.innerHTML = '';
            createDropdownsFromCategories('root', categories, []);
            selectFromCategories(categories, null);
        };

        var createDropdownsFromCategories = function (parentId, categories, path) {
            path.push(parentId);
            var level = path.length;
            if (level <= 5) {
                if (categories.length > 0) {
                    var sElem = document.createElement('select');
                    sElem.className = 'cat-' + parentId;
                    sElem.name = 'cat-' + parentId;
                    sElem.onchange = function (event) {
                        var selectedCat;
                        for (var i = 0; i < categories.length; i++) {
                            cat = categories[i];
                            if (cat._id === event.target.value) {
                                selectedCat = cat;
                                break;
                            }
                        }
                        if (!selectedCat) {
                            console.warn('Could not find category ' + event.target.value + ' in categories of ' + parentId);
                            selectedCat = categories[0];
                        }

                        if (selectedCat.subcategories.length <= 0) {
                            selectCategory(selectedCat);
                        } else {
                            var successfullySelected = selectFromCategories(selectedCat.subcategories);
                            if (!successfullySelected) {
                                selectCategory(selectedCat);
                            }
                        }
                    };
                    dropdownsRoot.appendChild(sElem);
                    for (var i = 0; i < categories.length; i++) {
                        var cat = categories[i];
                        [cat.selectElem, cat.path] = createDropdownsFromCategories(cat._id, cat.subcategories, path.slice());
                        var option = new Option(cat.name, cat._id);
                        sElem.options.add(option);
                        cat.optionElem = option;
                    }
                    return [sElem, path];
                }
            } else {
                console.warn('List for ' + parentId + ' found at level ' + level + 'which is too deep');
            }
            return [null, path];
        };

        var selectFromCategories = function (categories, selectedCategoryId = null) {
            var firstSelectableCategory,
                foundSelectedCategory = false;

            var walker = function (categories, level = 1) {
                if (foundSelectedCategory) {
                    return;
                }
                if (level <= 5) {
                    for (var i = 0; i < categories.length; i++) {
                        cat = categories[i];
                        walker(cat.subcategories, level + 1)
                        if (cat.subcategories.length <= 0) {
                            if (!firstSelectableCategory) {
                                firstSelectableCategory = cat;
                            }
                            if (selectedCategoryId && selectedCategoryId === cat._id) {
                                foundSelectedCategory = true;
                                selectCategory(cat);
                                break;
                            }
                        }
                    }
                } else {
                    console.warn('List found at level ' + level + ' which is too deep.');
                }
            };
            walker(categories);

            if (!foundSelectedCategory) {
                if (firstSelectableCategory) {
                    selectCategory(firstSelectableCategory);
                } else {
                    return false;
                }
            }
            return true;
        };

        var selectCategory = function (category) {
            var path = category.path;
            for (var i = 0; i < dropdownsRoot.children.length; i++) {
                var dropdown = dropdownsRoot.children[i];
                if (path.some(function (id) {
                        return 'cat-' + id === dropdown.className;
                    })) {
                    dropdown.style.display = 'inline-block';
                } else {
                    dropdown.style.display = 'none';
                }
            }
            for (var i = 0; i < path.length; i++) {
                var id = path[i];
                parentDropdownSearchLoop:
                    for (var j = 0; j < dropdownsRoot.children.length; j++) {
                        var dropdown = dropdownsRoot.children[j];
                        for (var k = 0; k < dropdown.options.length; k++) {
                            var option = dropdown.options[k];
                            if (option.value === id) {
                                option.selected = true;
                                break parentDropdownSearchLoop;
                            }
                        }
                    }
            }
        };

        refreshCategoryDropdowns();
    }
}, false);
