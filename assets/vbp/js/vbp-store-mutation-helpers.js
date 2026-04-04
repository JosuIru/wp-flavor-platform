/**
 * Visual Builder Pro - Store Mutation Helpers
 *
 * Helpers recursivos para mutar hijos de contenedores manteniendo
 * el versionado de nodos intermedios.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPStoreMutationHelpers = {
    mutateChildren: function(children, targetId, onMatch) {
        if (!children || children.length === 0) return false;

        for (var j = 0; j < children.length; j++) {
            if (children[j].id === targetId) {
                onMatch(children, j);
                return true;
            }

            if (children[j].children && children[j].children.length > 0) {
                if (this.mutateChildren(children[j].children, targetId, onMatch)) {
                    var intermediateVersion = (children[j]._version || 0) + 1;
                    children[j] = { ...children[j], _version: intermediateVersion };
                    return true;
                }
            }
        }

        return false;
    }
};
