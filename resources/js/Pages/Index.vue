<script setup>
import Workspace from "../Layouts/Workspace.vue";
import PageHeader from "../Components/Page/PageHeader.vue";
import Page from "../Components/Page/Page.vue";
import List from "../Components/List/List.vue";
import ListItem from "../Components/List/ListItem.vue";
import PageBody from "../Components/Page/PageBody.vue";
import Badge from "../Components/Badge/Badge.vue";
import {Link} from "@inertiajs/vue3"
import AnimateSearch from "../Components/Input/Search/AnimateSearch.vue";
import {ref} from "vue";
import Button from "../Components/Button/Button.vue";
import ImportDocumentModal from "./Parts/ImportDocumentModal.vue";
import EditDocumentModal from "./Parts/EditDocumentModal.vue";

const props = defineProps({
    templates: {
        type: Array,
        default: []
    }
})

const searchValue = ref()
const showModalImport = ref(false)
const showModalEdit = ref(false)
const editTemplateId = ref(null)
const vertical = ref(true)

const onChangeLayoutList = () => {
    vertical.value = !vertical.value
}
const onShowModalEdit = (template) => {
    showModalEdit.value = true
    editTemplateId.value = template.id
}
const onCloseModalEdit = () => {
    showModalEdit.value = false
    editTemplateId.value = null
}
</script>

<template>
    <Workspace>
        <Page>
            <template #header>
                <PageHeader>
                    Доступные шаблоны документов
                </PageHeader>
            </template>
            <PageBody>
                <div class="flex flex-col gap-y-2">
                    <div class="flex flex-row gap-x-2">
<!--                        <Calendar v-model="date" format="dd MMMM yyyy год" :return-formatted />-->
                        <AnimateSearch v-model="searchValue" />
<!--                        <Button :tag="Link" icon href="/editor">-->
<!--                            <template #icon>-->
<!--                                <svg xmlns="http://www.w3.org/2000/svg"-->
<!--                                     viewBox="0 0 24 24">-->
<!--                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">-->
<!--                                        <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>-->
<!--                                        <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"></path>-->
<!--                                        <path d="M12 11v6"></path>-->
<!--                                        <path d="M9 14h6"></path>-->
<!--                                    </g>-->
<!--                                </svg>-->
<!--                            </template>-->
<!--                        </Button>-->
                        <Button icon @click="showModalImport = true">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                    <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <path d="M12 11v6"></path>
                                    <path d="M9 14h6"></path>
                                </g>
                            </svg>
                        </Button>
                        <Button icon @click="onChangeLayoutList">
                            <svg v-if="vertical" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16"></path>
                                    <path d="M4 20h16"></path>
                                    <rect x="6" y="9" width="12" height="6" rx="2"></rect>
                                </g>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4v16"></path>
                                    <path d="M20 4v16"></path>
                                    <rect x="9" y="6" width="6" height="12" rx="2"></rect>
                                </g>
                            </svg>
                        </Button>
                    </div>
                    <List :vertical="vertical" class="h-[calc(100vh-224px)] overflow-y-auto pr-1">
                        <div v-for="template in templates"
                             :key="template.id"
                             class="relative"
                        >
                            <Link :href="`/contract-generator/${template.id}`"
                                  class="relative"
                            >
                                <ListItem>
                                    <template v-slot:header>
                                        {{ template.name }}
                                    </template>
                                    <div class="relative">
                                    <span v-if="template.description">
                                        {{ template.description }}
                                    </span>
                                    </div>
                                    <!--                                <template v-slot:actions>-->
                                    <!--                                    <Button :tag="Link" variant="ghost" icon :href="`/editor?templateId=${template.id}`">-->
                                    <!--                                        <template #icon>-->
                                    <!--                                            <svg xmlns="http://www.w3.org/2000/svg"-->
                                    <!--                                                 viewBox="0 0 24 24">-->
                                    <!--                                                <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">-->
                                    <!--                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>-->
                                    <!--                                                    <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"></path>-->
                                    <!--                                                    <path d="M12 11v6"></path>-->
                                    <!--                                                    <path d="M9 14h6"></path>-->
                                    <!--                                                </g>-->
                                    <!--                                            </svg>-->
                                    <!--                                        </template>-->
                                    <!--                                    </Button>-->
                                    <!--                                </template>-->
                                    <template v-slot:footer>
                                        <div class="flex gap-x-1.5">
                                            <Badge variant="primary">
                                                Экономический отдел
                                            </Badge>
                                        </div>
                                    </template>
                                </ListItem>
                            </Link>
                            <div class="absolute right-2 top-1/2 -translate-y-1/2 z-10">
                                <Button icon @click="onShowModalEdit(template)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                        <path d="M13.5 6.5l4 4" />
                                    </svg>
                                </Button>
                            </div>
                        </div>
                    </List>
                </div>
            </PageBody>
        </Page>
        <ImportDocumentModal v-model:open="showModalImport" @close="showModalImport = false" />
        <EditDocumentModal v-model:open="showModalEdit" :templateId="editTemplateId" @close="onCloseModalEdit" />
    </Workspace>
</template>

<style scoped>

</style>
