export const getFiles = () => {
    const context = import.meta.glob('./*.json', { eager: true });
    const modules = {};

    Object.entries(context).forEach(([key, resource]) => {
        const fileName = key.replace('./', '');
        const namespace = fileName.replace('.json', '');
        modules[namespace] = resource.default || resource;
    });

    return modules
}
