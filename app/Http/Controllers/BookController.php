<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookDownloads;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    //traer listado de todos los libros
    public function index(){
            $books = Book::with('authors', 'category', 'editorial')->get();
            return [
                "error" => false,
                "message" => "Successfull",
                "data" => $books
            ];
        }
    

    public function show($id){

        $book = Book::with('category','editorial','authors','bookDownloads')->where('id',$id)->get();
        if($book){
            return $this->getResponse200($book);
        }
        else{
            return $this->getResponse404();
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $existIsbn = Book::where("isbn", trim($request->isbn))->exists(); //Check if a registered book exists (duplicate ISBN)
            if (!$existIsbn) { //ISBN not registered
                $book = new Book();
                $book->isbn = trim($request->isbn);
                $book->title = $request->title;
                $book->description = $request->description;
                $book->published_date = date('y-m-d h:i:s'); //Temporarily assign the current date
                $book->category_id = $request->category["id"]; //recibir un objeto en el body de la peticion
                $book->editorial_id = $request->editorial["id"];
                $book->save();
                foreach ($request->authors as $item) { //Associate authors to book (N:M relationship)
                    $book->authors()->attach($item);
                }
                $bookDownload = new BookDownloads();
                $bookDownload->book_id = $book->id;
                $bookDownload->save();
                DB::commit();
                return $this->getResponse201('book', 'created', $book);
            } else {
                return $this->getResponse500(['The isbn field must be unique']);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e]);
        }
    }


    public function update(Request $request,$id)
    {
        DB::beginTransaction();
        try {
            //$isbn = preg_replace('/\s+/', '\u0020', $request->isbn); //Remove blank spaces from ISBN
            $existBook = Book::where("id", $id)->exists(); //Check if a registered book exists (duplicate ISBN)

            if ($existBook) { //ISBN not registered
                $book = Book::get()->where("id", $id)->first();
                $book->isbn = trim($request->isbn);
                $book->title = $request->title;
                $book->description = $request->description;
                $book->published_date = date('y-m-d h:i:s'); //Temporarily assign the current date
                $book->category_id = $request->category["id"]; //recibir un objeto en el body de la peticion
                $book->editorial_id = $request->editorial["id"];
                $book->update();
                foreach ($book->authors as $item) { //deissasociate authors to book (N:M relationship)
                    $book->authors()->detach($item->id);
                }
                foreach ($request->authors as $item) { //deissasociate authors to book (N:M relationship)
                    $book->authors()->attach($item);
                }
                $book = Book::with('category','editorial','authors')->where("id",$id)->get();
                DB::commit();
                return $this->getResponse201('book', 'updated', $book);
            } else {
                return $this->getResponse500(['The book is not registered']);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e]);
        }
    }

    public function destroy($id){
        // $book = Book::get()->where('id',$id)->first();//al solo obtener un registro, usar first
        $book = Book::find($id);
        try{
            if ($book) {
                foreach ($book->authors as $item) {
                $book->authors()->detach($item->id);
                }
                $book->bookDownloads()->delete();
                $book->delete();
                return $this->getResponseDelete200("book");
            }else{
                return $this->getResponseDelete404();
            }
        }catch (Exception $e){
            return $this->getResponse500([$e->getMessage()]);
        }
            // if ($book != null) {
            //     foreach($book->authors as $author){
            //         $book->authors()->detach($author->id);
            //     }
            //     foreach($book->bookDownloads as $books){
            //         $book->bookDownloads()->detach($books->id);
            //     }

            //     //$book->bookDownloads()->detach();
            //     $book->delete();
            //     return $this->getResponseDelete200('book');
            // } else {
            //     return $this->getResponse404();
            // }
}

public function addBookReview(Request $request){
    $validator = Validator::make($request->all(), [
        'comment' => 'required'
    ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try{

            }catch (Exception $e){
                return $this->getResponse201('book review','created',auth()->$user());
            }

            //$book->bookDownloads()->detach();
            $book->delete();
            return $this->getResponseDelete200('book');
        } else {
            return $this->getResponse404();
        }
}

}

