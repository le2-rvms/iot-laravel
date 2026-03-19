const componentGroups = [
    ['DropdownMenu', '@/components/ui/dropdown-menu'],
    ['Breadcrumb', '@/components/ui/breadcrumb'],
    ['Pagination', '@/components/ui/pagination'],
    ['Separator', '@/components/ui/separator'],
    ['Skeleton', '@/components/ui/skeleton'],
    ['Checkbox', '@/components/ui/checkbox'],
    ['Dialog', '@/components/ui/dialog'],
    ['Button', '@/components/ui/button'],
    ['Avatar', '@/components/ui/avatar'],
    ['Badge', '@/components/ui/badge'],
    ['Alert', '@/components/ui/alert'],
    ['Input', '@/components/ui/input'],
    ['Label', '@/components/ui/label'],
    ['Card', '@/components/ui/card'],
    ['Sheet', '@/components/ui/sheet'],
    ['Table', '@/components/ui/table'],
    ['Sonner', '@/components/ui/sonner'],
];

export function uiResolver() {
    return {
        type: 'component',
        resolve(name) {
            if (!name.startsWith('Ui')) {
                return;
            }

            const importName = name.slice(2);
            const normalizedImportName = importName === 'Sonner' ? 'Toaster' : importName;
            const group = componentGroups.find(([prefix]) => normalizedImportName.startsWith(prefix));

            if (!group) {
                return;
            }

            return {
                name: normalizedImportName,
                from: group[1],
            };
        },
    };
}
