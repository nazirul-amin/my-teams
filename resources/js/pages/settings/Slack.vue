<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { connect, destroy, edit } from '@/routes/slack';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SlackAccount {
    slack_user_id: string;
    slack_team_id?: string | null;
    slack_team_name?: string | null;
    slack_name?: string | null;
    slack_email?: string | null;
    slack_avatar?: string | null;
    connected_at?: string | null;
}

interface Props {
    status?: string | null;
    error?: string | null;
    slackAccount?: SlackAccount | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Slack',
        href: edit().url,
    },
];

const initials = computed(() => {
    const value = props.slackAccount?.slack_name || props.slackAccount?.slack_email || 'Slack';

    return value
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Slack" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Slack"
                    description="Connect your Slack account for team based workflows and future Slack integrations."
                />

                <Alert v-if="status">
                    <AlertTitle>Updated</AlertTitle>
                    <AlertDescription>{{ status }}</AlertDescription>
                </Alert>

                <Alert v-if="error" variant="destructive">
                    <AlertTitle>Unable to connect</AlertTitle>
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>

                <div class="rounded-xl border bg-card p-6 shadow-xs">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-4">
                            <Badge :variant="slackAccount ? 'default' : 'secondary'">
                                {{ slackAccount ? 'Connected' : 'Not connected' }}
                            </Badge>

                            <div v-if="slackAccount" class="flex items-center gap-4">
                                <Avatar class="h-12 w-12">
                                    <AvatarImage
                                        v-if="slackAccount.slack_avatar"
                                        :src="slackAccount.slack_avatar"
                                        :alt="slackAccount.slack_name ?? 'Slack avatar'"
                                    />
                                    <AvatarFallback>{{ initials }}</AvatarFallback>
                                </Avatar>

                                <div class="space-y-1">
                                    <p class="font-medium">
                                        {{ slackAccount.slack_name || 'Slack user' }}
                                    </p>
                                    <p
                                        v-if="slackAccount.slack_email"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ slackAccount.slack_email }}
                                    </p>
                                    <p
                                        v-if="slackAccount.slack_team_name"
                                        class="text-sm text-muted-foreground"
                                    >
                                        Workspace: {{ slackAccount.slack_team_name }}
                                    </p>
                                </div>
                            </div>

                            <p v-else class="max-w-lg text-sm text-muted-foreground">
                                Connect Slack to link each user account with their Slack identity.
                                This stores the Slack profile needed for future Slack-based features.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button v-if="!slackAccount" as-child>
                                <Link :href="connect()">Connect Slack</Link>
                            </Button>

                            <Button v-else as-child variant="destructive">
                                <Link :href="destroy()" method="delete" as="button">
                                    Disconnect Slack
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
