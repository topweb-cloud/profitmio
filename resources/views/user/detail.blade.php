@extends('layouts.base', [
    'hasSidebar' => false
])

@section('head-styles')
    <link href="{{ asset('css/user-detail.css') }}" rel="stylesheet">
@endsection

@section('body-script')
    <script>
        window.getCompanyUrl = @json(route('company.for-dropdown'));
        window.searchCampaignFormUrl = "{{ route('campaign.for-user-display') }}";
        window.searchCompaniesFormUrl = "{{ route('company.for-user-display') }}";
        window.campaignCompanySelected = @json($campaignCompanySelected);
        window.updateUserUrl = "{{ route('user.update', ['user' => ':userId']) }}";
        window.timezones = @json($timezones);
        window.updateCompanyDataUrl = "{{ route('user.update-company-data', ['user' => ':userId']) }}";
        window.user = @json($user);
        window.deleteUserUrl = "{{ route('user.delete', ['user' => $user->id]) }}";
        window.updateUserPhotoUrl = "{{ route('user.update-avatar', ['user' => $user->id]) }}";
        window.campaignStatsUrl = "{{ route('campaigns.stats', ['campaign' => ':campaignId']) }}";
        window.campaignDropIndex = "{{ route('campaigns.drops.index', ['campaign' => ':campaignId']) }}";
        window.campaignRecipientIndex = "{{ route('campaigns.recipient-lists.index', ['campaign' => ':campaignId']) }}";
        window.campaignResponseConsoleIndex = "{{ route('campaign.response-console.index', ['campaign' => ':campaignId']) }}";
        window.campaignQ = @json($campaignQ);
        @if (auth()->user()->isAdmin())
            window.userRole = 'site_admin';
        @else
            window.userRole = @json(auth()->user()->getRole(App\Models\Company::findOrFail(get_active_company())));
        @endif
        window.userIndexUrl = "{{ route('user.index') }}";
    </script>
    <script src="{{ asset('js/user-detail.js') }}"></script>
@endsection

{{--@section('sidebar-toggle-content')--}}
    {{--<i class="fas fa-chevron-circle-left mr-2"></i>User Details--}}
{{--@endsection--}}

@section('sidebar-content')
    <div id="sidebar-content" v-cloak>
        <div class="avatar">
            <div class="avatar--image" :style="{backgroundImage: 'url(\'' + user.image_url + '\')'}" v-if="showAvatarImage">
                <button class="avatar--edit" v-if="enableInputs" @click="showAvatarImage = false">
                    <i class="fas fa-pencil-alt"></i>
                </button>
            </div>
            <vue-dropzone id="profile-image" :options="dropzoneOptions" :useCustomSlot="true" @vdropzone-success="profileImageUploaded" @vdropzone-error="profileImageError" v-if="!showAvatarImage">
                <div class="dropzone-upload-profile-image">
                    <h3 class="dropzone-title">Drag and drop to upload content!</h3>
                    <div class="dropzone-subtitle">...or click to select a file from your computer</div>
                </div>
            </vue-dropzone>
        </div>
        <button class="btn pm-btn pm-btn-blue edit-user" v-if="!enableInputs && (loggedUserRole === 'site_admin' || (loggedUserRole === 'admin' && user.role !== 'site_admin'))" @click="enableInputs = !enableInputs">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <form class="clearfix form" method="post" action="{{ route('user.update', ['user' => $user->id]) }}" @submit.prevent="saveUser">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control empty" name="first_name" placeholder="First Name" v-model="editUserForm.first_name" required v-if="enableInputs">
                <p class="form-control panel-data" v-if="!enableInputs">@{{ user.first_name }}</p>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" name="last_name" placeholder="Last Name" v-model="editUserForm.last_name" required v-if="enableInputs">
                <p class="form-control panel-data" v-if="!enableInputs">@{{ user.last_name }}</p>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" name="email" placeholder="Email" v-model="editUserForm.email" required v-if="enableInputs">
                <p class="form-control panel-data" v-if="!enableInputs">@{{ user.email }}</p>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" class="form-control" name="phone_number" placeholder="Phone Number" v-model="editUserForm.phone_number" v-if="enableInputs">
                <p class="form-control panel-data" v-if="!enableInputs">@{{ user.phone_number }}</p>
            </div>
            <button class="btn pm-btn pm-btn-purple float-left mt-4" type="submit" :disabled="loading" v-if="enableInputs">
                <span v-if="!loading"><i class="fas fa-save mr-2"></i>Save</span>
                <div class="loader-spinner" v-if="loading">
                    <spinner-icon></spinner-icon>
                </div>
            </button>
            {{--<button class="btn pm-btn pm-btn-blue float-right mt-4" type="button">Change Password</button>--}}
        </form>
        <button v-if="loggedUserRole === 'site_admin'" class="btn pm-btn pm-btn-danger delete-user" type="button" @click="deleteUser"><i class="fas fa-trash-alt"></i></button>
    </div>
@endsection

@section('main-content')
    <div class="container" id="user-view" v-cloak>
        <a class="btn pm-btn pm-btn-blue go-back mb-3" href="{{ route('user.index') }}">
            <i class="fas fa-arrow-circle-left mr-2"></i> Go Back
        </a>
        <div class="card profile mb-5">
            <div class="card-body pb-5">
                <div class="row no-gutters">
                    <div class="col-12 col-md-4 col-lg-3 col-xl-2 company-image-container">
                        <img class="rounded rounded-circle img-thumbnail" src="{{ $user->image_url }}" width="150px" height="150px">
                    </div>
                    <div class="col-12 col-md-8 col-lg-9 col-xl-10">
                        <div class="d-flex justify-content-end">
                            <button class="btn pm-btn pm-btn-outline-purple mb-3" @click="showUserFormControls = true" v-if="!showUserFormControls">
                                <i class="fas fa-pencil-alt mr-2"></i>
                                Edit
                            </button>
                        </div>
                        <form @submit.prevent="saveUser">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <p v-if="!showUserFormControls" class="editable form-control">@{{ originalUser.first_name }}</p>
                                        <input name="first_name" tabindex="1" class="form-control" v-model="user.first_name" aria-label="First name" v-if="showUserFormControls">
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <p v-if="!showUserFormControls" class="editable form-control">@{{ originalUser.email }}</p>
                                        <input name="email" tabindex="3" class="form-control" v-model="user.email" aria-label="Email" v-if="showUserFormControls">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <p v-if="!showUserFormControls" class="editable form-control">@{{ originalUser.last_name }}</p>
                                        <input name="last_name" tabindex="2" class="form-control" v-model="user.last_name" aria-label="Last name" v-if="showUserFormControls">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <p v-if="!showUserFormControls" class="editable company-address form-control">@{{ originalUser.phone_number }}</p>
                                        <input name="phone" tabindex="4" class="form-control" v-model="user.phone_number" aria-label="Company Phone" v-if="showUserFormControls">
                                    </div>
                                </div>
                                <div class="col-12 form-controls">
                                    <div class="form-group" v-if="showUserFormControls">
                                        <button class="btn btn-sm btn-outline-primary mr-1" type="submit">
                                            Save
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelUser">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                </div>
            </div>
        </div>
        <b-card no-body>
            <b-tabs card>
                <b-tab title="CAMPAIGN" active>
                    @if($hasCampaigns)
                    <div class="row align-items-end no-gutters mb-md-3">
                        <div class="col-12 col-sm-5 col-lg-4">
                            <div class="form-group filter--form-group">
                                <label>Filter By Company</label>
                                <v-select :options="companies" label="name" v-model="campaignCompanySelected" class="filter--v-select" @input="onCampaignCompanySelected"></v-select>
                            </div>
                        </div>
                        <div class="col-none col-sm-2 col-lg-4"></div>
                        <div class="col-12 col-sm-5 col-lg-4">
                            <input type="text" v-model="searchCampaignForm.q" class="form-control filter--search-box" aria-describedby="search"
                                   placeholder="Search" @keyup.enter="fetchCampaigns">
                        </div>
                    </div>
                    @endif
                    <div class="row align-items-end no-gutters mt-3">
                        <div class="col-12">
                            <div class="loader-spinner" v-if="loadingCampaigns">
                                <spinner-icon></spinner-icon>
                            </div>
                            <div class="no-items-row" v-if="countActiveCampaigns === 0 && countInactiveCampaigns === 0">
                                No Items
                            </div>
                            <div class="campaign-group-label" v-if="countActiveCampaigns > 0">ACTIVE</div>
                            <campaign v-for="campaign in campaigns" v-if="campaign.status === 'Active'" :key="campaign.id" :campaign="campaign"></campaign>
                            <div class="campaign-group-label" v-if="countInactiveCampaigns > 0">INACTIVE</div>
                            <campaign v-for="campaign in campaigns" v-if="campaign.status !== 'Active'" :key="campaign.id" :campaign="campaign"></campaign>
                            @if($hasCampaigns)
                            <pm-pagination :pagination="campaignsPagination" @page-changed="onCampaignPageChanged"></pm-pagination>
                            @endif
                        </div>
                    </div>
                </b-tab>
                <b-tab title="COMPANY">
                    <div class="row align-items-end no-gutters mb-md-4">
                        <div class="col-12 col-sm-5 col-lg-4">
                        </div>
                        <div class="col-none col-sm-2 col-lg-4"></div>
                        <div class="col-12 col-sm-5 col-lg-4">
                            <input type="text" v-model="searchCompanyForm.q" class="form-control filter--search-box" aria-describedby="search"
                                   placeholder="Search" @keyup.enter="fetchCompanies">
                        </div>
                    </div>
                    <div class="row align-items-end no-gutters mt-3">
                        <div class="col-12">
                            <div class="loader-spinner" v-if="loadingCompanies">
                                <spinner-icon></spinner-icon>
                            </div>
                            <div class="no-items-row" v-if="countCompanies === 0">
                                No Items
                            </div>
                            <div class="company" v-for="company in companiesForList">
                                <div class="row no-gutters">
                                    <div class="col-12 col-md-4 company-info">
                                        <div class="company-info--image">
                                            <img src="" alt="">
                                        </div>
                                        <div class="company-info--data">
                                            <strong>@{{ company.name }}</strong>
                                            <p>@{{ company.address }}</p>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-3 company-role">
                                        @if (!$user->isAdmin())
                                        <v-select :options="roles" :disabled="!canEditCompanyData(company)" v-model="company.role" class="filter--v-select" @input="updateCompanyData(company)" :clearable="false">
                                            <template slot="selected-option" slot-scope="option">
                                                @{{ option.label | userRole }}
                                            </template>
                                            <template slot="option" slot-scope="option">
                                                @{{ option.label | userRole }}
                                            </template>
                                        </v-select>
                                        @else
                                            <user-role :short-version="false" :role="'site_admin'"></user-role>
                                        @endif
                                    </div>
                                    <div class="col-4 col-md-3 company-timezone">
                                        <v-select :options="timezones" :disabled="!canEditCompanyData(company)" v-model="company.timezone" class="filter--v-select" @input="updateCompanyData(company)" :clearable="false">
                                            <template slot="selected-option" slot-scope="option">
                                                @{{ option.label }}
                                            </template>
                                            <template slot="option" slot-scope="option">
                                                @{{ option.label }}
                                            </template>
                                        </v-select>
                                    </div>
                                    <div class="col-4 col-md-2 company-active-campaigns">
                                        <small>Active Campaigns</small>
                                        <div>
                                            <span class="pm-font-campaigns-icon"></span>
                                            <span class="company-active-campaigns--counter">@{{ company.active_campaigns_for_user }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <pm-pagination :pagination="companiesPagination" @page-changed="onCompanyPageChanged"></pm-pagination>
                        </div>
                    </div>
                </b-tab>
            </b-tabs>
        </b-card>
    </div>
@endsection
