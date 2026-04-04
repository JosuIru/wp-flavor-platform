/**
 * Visual Builder Pro - Store Tree Helpers
 *
 * Helpers puros para recorrer el árbol de elementos.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPStoreTreeHelpers = {
    getElementDeep: function(elements, getElement, id) {
        var element = getElement(id);
        if (element) return element;

        function findInChildren(children) {
            if (!children || children.length === 0) return null;
            for (var j = 0; j < children.length; j++) {
                if (children[j].id === id) {
                    return children[j];
                }
                if (children[j].children && children[j].children.length > 0) {
                    var found = findInChildren(children[j].children);
                    if (found) return found;
                }
            }
            return null;
        }

        for (var i = 0; i < elements.length; i++) {
            if (elements[i].children && elements[i].children.length > 0) {
                var found = findInChildren(elements[i].children);
                if (found) return found;
            }
        }

        return null;
    },

    getElementPath: function(elements, id) {
        var path = [{ id: 'root', name: 'Página', type: 'root' }];

        function findPath(nodes, targetId, currentPath) {
            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                var newPath = currentPath.concat([{
                    id: el.id,
                    name: el.name || el.type,
                    type: el.type
                }]);

                if (el.id === targetId) {
                    return newPath;
                }

                if (el.children && el.children.length > 0) {
                    var foundPath = findPath(el.children, targetId, newPath);
                    if (foundPath) return foundPath;
                }
            }
            return null;
        }

        var elementPath = findPath(elements, id, []);
        if (elementPath) {
            path = path.concat(elementPath);
        }

        return path;
    }
};
