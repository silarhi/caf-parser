import { defineSilarhiDocs } from '@silarhi/docs-kit'

export default defineSilarhiDocs({
    slug: 'caf-parser',
    title: 'CAF Parser',
    description: 'A PHP parser for CAF LA44ZZ payment slips.',
    sidebar: [
        { label: 'Getting started', items: [{ autogenerate: { directory: 'getting-started' } }] },
        { label: 'Guides', items: [{ autogenerate: { directory: 'guides' } }] },
        { label: 'Reference', items: [{ autogenerate: { directory: 'reference' } }] },
    ],
})
