<script setup>
import Workspace from "../Layouts/Workspace.vue";
import {HomeIcon} from '@heroicons/vue/16/solid'
import PageHeader from "../Components/Page/PageHeader.vue";
import Page from "../Components/Page/Page.vue";
import Sidebar from "../Components/Sidebar/Sidebar.vue";
import List from "../Components/List/List.vue";
import ListItem from "../Components/List/ListItem.vue";
import PageBody from "../Components/Page/PageBody.vue";
import Badge from "../Components/Badge/Badge.vue";
import Input from "../Components/Input/Input.vue";
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
                <div class="flex flex-col gap-y-4">
                    <Input />
                    <List>
                        <ListItem>
                            <template v-slot:header>
                                Шаблон документа №123
                            </template>
                            <template v-slot:footer>
                                <div class="flex gap-x-1.5">
                                    <Badge variant="primary">
                                        Экономический отдел
                                    </Badge>
                                </div>
                            </template>
                        </ListItem>
                        <ListItem>
                            <template v-slot:header>
                                Шаблон документа №123
                            </template>
                            <template v-slot:footer>
                                <div class="flex gap-x-1.5">
                                    <Badge variant="primary">
                                        Экономический отдел
                                    </Badge>
                                </div>
                            </template>
                        </ListItem>
                        <ListItem>
                            <template v-slot:header>
                                Шаблон документа №123
                            </template>
                            <template v-slot:footer>
                                <div class="flex gap-x-1.5">
                                    <Badge variant="primary">
                                        Экономический отдел
                                    </Badge>
                                </div>
                            </template>
                        </ListItem>
                        <ListItem>
                            <template v-slot:header>
                                Шаблон документа №123
                            </template>
                            <template v-slot:footer>
                                <div class="flex gap-x-1.5">
                                    <Badge variant="primary">
                                        Экономический отдел
                                    </Badge>
                                </div>
                            </template>
                        </ListItem>
                        <ListItem>
                            <template v-slot:header>
                                Шаблон документа №123
                            </template>
                            <template v-slot:footer>
                                <div class="flex gap-x-1.5">
                                    <Badge variant="primary">
                                        Экономический отдел
                                    </Badge>
                                </div>
                            </template>
                        </ListItem>
                    </List>
                </div>
            </PageBody>
        </Page>
    </Workspace>
</template>

<style scoped>

</style>
