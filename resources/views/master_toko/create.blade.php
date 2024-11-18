@extends('layouts.app')

@section('title', 'Tambah Toko')

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <b>Tambah Toko</b>
                        </div>
                    </div>
                    <hr>
                </div>
                <div class="card-body">
                    <form action="{{ route('master-toko.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="kd_toko" class="form-label">Kode Toko</label>
                                <input type="text" class="form-control @error('kd_toko') is-invalid @enderror"
                                    id="kd_toko" name="kd_toko" placeholder="Kode Toko" value="{{ old('kd_toko') }}">

                                @error('kd_toko')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="nama_toko" class="form-label">Nama Toko</label>
                                <input type="text" class="form-control @error('nama_toko') is-invalid @enderror"
                                    id="nama_toko" name="nama_toko" placeholder="Nama Toko" value="{{ old('nama_toko') }}">

                                @error('nama_toko')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="alamat" class="form-label">Alamat</label>

                                <textarea class="form-control @error('alamat') is-invalid @enderror" name="alamat" id="alamat" placeholder="Alamat">{{ old('alamat') }}</textarea>

                                @error('alamat')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="kd_provinsi" class="form-label">Provinsi</label>
                                <select class="form-select @error('kd_provinsi') is-invalid @enderror" name="kd_provinsi"
                                    id="kd_provinsi">
                                    <option value="">Pilih Provinsi</option>
                                    <option value="1" @selected(old('1') == '1')>Kalimantan Selatan</option>
                                    <option value="2" @selected(old('2') == '2')>Kalimantan Tengah</option>
                                </select>

                                @error('kd_provinsi')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" name="status"
                                    id="status">
                                    <option value="">Pilih Status</option>
                                    <option value="active" @selected(old('active') == 'active')>Active</option>
                                    <option value="inactive" @selected(old('inactive') == 'inactive')>Inactive</option>
                                </select>

                                @error('status')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror"
                                    id="latitude" name="latitude" placeholder="Latitude" value="{{ old('latitude') }}">

                                @error('latitude')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror"
                                    id="longitude" name="longitude" placeholder="Longitude" value="{{ old('longitude') }}">

                                @error('longitude')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select @error('category') is-invalid @enderror" name="category"
                                    id="category">
                                    <option value="">Pilih Category</option>
                                    <option value="2W" @selected(old('2W') == '2W')>2W</option>
                                    <option value="4W" @selected(old('4W') == '4W')>4W</option>
                                </select>

                                @error('category')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="frekuensi" class="form-label">Frekuensi Kunjungan / Bulan</label>
                                <input type="number" class="form-control @error('frekuensi') is-invalid @enderror"
                                    id="frekuensi" name="frekuensi" placeholder="Frekuensi" value="{{ old('frekuensi') }}">

                                @error('frekuensi')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="user_sales" class="form-label">Sales</label>
                                <select name="user_sales" id="user_sales"
                                    class="form-select @error('user_sales') is-invalid @enderror">
                                    <option value="">Piilih Sales</option>
                                    @foreach ($salesman as $sales)
                                        <option value="{{ $sales->username }}">
                                            {{ $sales->username }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('user_sales')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
