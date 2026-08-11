@extends('layouts.quan-ly')

@section('title', 'Trang chủ')

@section('content')
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card card-panel h-100">
                <div class="card-header">Danh mục</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('dm.giao-vien.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Quản lý giáo viên</span>
                        <span class="text-muted small">→</span>
                    </a>
                    <a href="{{ route('dm.xe.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Quản lý xe</span>
                        <span class="text-muted small">→</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card card-panel h-100">
                <div class="card-header">Lịch</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('lich.gv.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Lịch làm việc giáo viên</span>
                        <span class="text-muted small">→</span>
                    </a>
                    <a href="{{ route('lich.xe.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Lịch sử dụng xe tập lái</span>
                        <span class="text-muted small">→</span>
                    </a>
                    <a href="{{ route('lich.ly-thuyet.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Thêm lịch lý thuyết</span>
                        <span class="text-muted small">→</span>
                    </a>
                    <a href="{{ route('lich.thuc-hanh.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Thêm lịch thực hành</span>
                        <span class="text-muted small">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
